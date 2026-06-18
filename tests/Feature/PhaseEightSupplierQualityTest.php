<?php

namespace Tests\Feature;

use App\Models\CalibrationRecord;
use App\Models\CorrectiveAction;
use App\Models\Document;
use App\Models\EquipmentAsset;
use App\Models\Supplier;
use App\Models\SupplierCertificate;
use App\Models\SupplierEvaluation;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseEightSupplierQualityTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_eight_supplier_quality_overview_returns_seeded_records(): void
    {
        $this->seed();

        $tenant = $this->tenant();
        $jojo = $this->user('jojo@iso-forge.test');

        $this->withToken($this->tokenFor($jojo))
            ->getJson("/api/tenants/{$tenant->slug}/supplier-quality")
            ->assertOk()
            ->assertJsonCount(1, 'data.suppliers')
            ->assertJsonCount(1, 'data.evaluations')
            ->assertJsonCount(1, 'data.certificates')
            ->assertJsonCount(1, 'data.equipment_assets')
            ->assertJsonCount(1, 'data.calibration_records')
            ->assertJsonPath('data.suppliers.0.supplier_code', 'SUP-MANGO-01')
            ->assertJsonPath('data.suppliers.0.approval_status', 'Approved')
            ->assertJsonPath('data.equipment_assets.0.asset_tag', 'PAST-THERM-01')
            ->assertJsonPath('data.calibration_records.0.result', 'Pass');

        $this->withToken($this->tokenFor($jojo))
            ->getJson("/api/tenants/{$tenant->slug}/snapshot")
            ->assertOk()
            ->assertJsonPath('metrics.approved_suppliers', 1)
            ->assertJsonPath('metrics.supplier_certificates_expiring', 0)
            ->assertJsonPath('metrics.critical_equipment', 1)
            ->assertJsonPath('metrics.calibrations_due', 0);
    }

    public function test_phase_eight_supplier_quality_viewer_cannot_create_supplier(): void
    {
        $this->seed();

        $tenant = $this->tenant();
        $auditor = $this->user('auditor@iso-forge.test');

        $this->withToken($this->tokenFor($auditor))
            ->getJson("/api/tenants/{$tenant->slug}/supplier-quality")
            ->assertOk();

        $this->withToken($this->tokenFor($auditor))
            ->postJson("/api/tenants/{$tenant->slug}/supplier-quality/suppliers", [
                'name' => 'Read-only Supplier',
                'supplier_code' => 'SUP-RO-01',
                'category' => 'Packaging',
            ])
            ->assertForbidden();
    }

    public function test_phase_eight_manager_can_create_supplier_evaluation_and_certificate(): void
    {
        $this->seed();

        $tenant = $this->tenant();
        $jojo = $this->user('jojo@iso-forge.test');
        $document = Document::query()->where('tenant_id', $tenant->id)->where('status', 'Approved')->firstOrFail();
        $token = $this->tokenFor($jojo);

        $supplierResponse = $this->withToken($token)
            ->postJson("/api/tenants/{$tenant->slug}/supplier-quality/suppliers", [
                'name' => 'Phnom Penh Bottle Works',
                'supplier_code' => 'SUP-BOTTLE-01',
                'category' => 'Packaging',
                'contact_email' => 'qa@bottle-works.example',
                'owner_id' => $jojo->id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.approval_status', 'Pending');

        $evaluationResponse = $this->withToken($token)
            ->postJson("/api/tenants/{$tenant->slug}/supplier-quality/suppliers/{$supplierResponse->json('data.id')}/evaluations", [
                'evaluated_by_id' => $jojo->id,
                'evaluation_date' => now()->toDateString(),
                'score' => 72,
                'result' => 'Conditional',
                'next_review_date' => now()->addMonths(3)->toDateString(),
                'evidence_document_id' => $document->id,
                'notes' => 'Conditional approval until packaging migration-risk evidence is reviewed.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.result', 'Conditional')
            ->assertJsonPath('data.supplier.risk_level', 'High');

        $certificateResponse = $this->withToken($token)
            ->postJson("/api/tenants/{$tenant->slug}/supplier-quality/suppliers/{$supplierResponse->json('data.id')}/certificates", [
                'document_id' => $document->id,
                'certificate_type' => 'Food-contact declaration',
                'certificate_number' => 'FCD-PPBW-2026',
                'issued_at' => now()->subMonth()->toDateString(),
                'expires_at' => now()->addDays(15)->toDateString(),
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'Expiring');

        $supplier = Supplier::query()->findOrFail($supplierResponse->json('data.id'));

        $this->assertSame('Conditional', $supplier->approval_status);
        $this->assertSame('High', $supplier->risk_level);
        $this->assertSame(2, Supplier::query()->where('tenant_id', $tenant->id)->count());
        $this->assertSame(2, SupplierEvaluation::query()->where('tenant_id', $tenant->id)->count());
        $this->assertSame(2, SupplierCertificate::query()->where('tenant_id', $tenant->id)->count());
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'supplier_quality.evaluation.created',
            'auditable_id' => $evaluationResponse->json('data.id'),
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'supplier_quality.certificate.created',
            'auditable_id' => $certificateResponse->json('data.id'),
        ]);
    }

    public function test_phase_eight_failed_calibration_places_equipment_on_hold_and_creates_capa(): void
    {
        $this->seed();

        $tenant = $this->tenant();
        $jojo = $this->user('jojo@iso-forge.test');
        $token = $this->tokenFor($jojo);

        $assetResponse = $this->withToken($token)
            ->postJson("/api/tenants/{$tenant->slug}/supplier-quality/equipment", [
                'asset_tag' => 'SCALE-FILL-02',
                'name' => 'Filling scale checkweigher',
                'location' => 'Packing line 2',
                'owner_id' => $jojo->id,
                'calibration_interval_days' => 90,
                'critical_to_food_safety' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'Active');

        $calibrationResponse = $this->withToken($token)
            ->postJson("/api/tenants/{$tenant->slug}/supplier-quality/equipment/{$assetResponse->json('data.id')}/calibrations", [
                'performed_by_id' => $jojo->id,
                'performed_at' => now()->toDateString(),
                'due_at' => now()->addDays(7)->toDateString(),
                'result' => 'Fail',
                'certificate_number' => 'CAL-FAIL-2606',
                'notes' => 'Checkweigher failed repeatability tolerance.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.result', 'Fail')
            ->assertJsonPath('data.equipment_asset.status', 'Hold')
            ->assertJsonPath('data.corrective_action.status', 'Open');

        $this->assertSame('Hold', EquipmentAsset::query()->findOrFail($assetResponse->json('data.id'))->status);
        $this->assertSame(1, CalibrationRecord::query()->where('tenant_id', $tenant->id)->where('result', 'Fail')->count());
        $this->assertSame(2, CorrectiveAction::query()->where('tenant_id', $tenant->id)->whereNotIn('status', ['Closed', 'Verified'])->count());
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'supplier_quality.calibration_capa.created',
            'auditable_id' => $calibrationResponse->json('data.corrective_action_id'),
        ]);
    }

    private function tenant(): Tenant
    {
        return Tenant::query()->where('slug', 'angkor-quality-foods')->firstOrFail();
    }

    private function user(string $email): User
    {
        return User::query()->where('email', $email)->firstOrFail();
    }

    private function tokenFor(User $user): string
    {
        return $user->createToken('phase-eight-test')->plainTextToken;
    }
}
