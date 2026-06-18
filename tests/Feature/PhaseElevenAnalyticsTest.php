<?php

namespace Tests\Feature;

use App\Models\CalibrationRecord;
use App\Models\CorrectiveAction;
use App\Models\EquipmentAsset;
use App\Models\IncidentReport;
use App\Models\Supplier;
use App\Models\SupplierCertificate;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseElevenAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_eleven_analytics_returns_seeded_trend_dashboard(): void
    {
        $this->seed();

        $tenant = $this->tenant();
        $jojo = $this->user('jojo@iso-forge.test');

        $this->withToken($this->tokenFor($jojo))
            ->getJson("/api/tenants/{$tenant->slug}/analytics")
            ->assertOk()
            ->assertJsonPath('data.incident_trends.total', 1)
            ->assertJsonPath('data.incident_trends.open', 0)
            ->assertJsonPath('data.incident_trends.by_status.Contained', 1)
            ->assertJsonPath('data.incident_trends.by_severity.Minor', 1)
            ->assertJsonPath('data.incident_trends.recent.0.reference', 'IR-2026-0001')
            ->assertJsonPath('data.capa_ageing.open_total', 1)
            ->assertJsonPath('data.capa_ageing.buckets.due_next_30_days', 1)
            ->assertJsonPath('data.training_competency.programs', 1)
            ->assertJsonPath('data.training_competency.assignments_by_status.Completed', 1)
            ->assertJsonPath('data.training_competency.records_by_competency_status.Competent', 1)
            ->assertJsonPath('data.training_competency.pass_rate', 100)
            ->assertJsonPath('data.supplier_risk.suppliers_by_risk_level.Medium', 1)
            ->assertJsonPath('data.supplier_risk.suppliers_by_approval_status.Approved', 1)
            ->assertJsonPath('data.supplier_risk.certificates_expiring_30_days', 0)
            ->assertJsonPath('data.supplier_risk.calibrations_due_30_days', 0);
    }

    public function test_phase_eleven_analytics_view_permission_is_required(): void
    {
        $this->seed();

        $tenant = $this->tenant();
        $blockedUser = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Analytics Blocked User',
            'email' => 'analytics-blocked@iso-forge.test',
            'job_title' => 'Temporary Viewer',
            'password' => 'password',
        ]);

        $this->withToken($this->tokenFor($blockedUser))
            ->getJson("/api/tenants/{$tenant->slug}/analytics")
            ->assertForbidden();

    }

    public function test_phase_eleven_analytics_recalculates_after_new_risk_records(): void
    {
        $this->seed();

        $tenant = $this->tenant();
        $jojo = $this->user('jojo@iso-forge.test');

        IncidentReport::create([
            'tenant_id' => $tenant->id,
            'reference' => 'IR-2026-0100',
            'title' => 'Unreleased product exposure',
            'incident_type' => 'Food Safety',
            'severity' => 'Critical',
            'status' => 'Open',
            'reported_by_id' => $jojo->id,
            'owner_id' => $jojo->id,
            'detected_at' => now(),
            'description' => 'Open incident for analytics recalculation.',
        ]);

        CorrectiveAction::create([
            'tenant_id' => $tenant->id,
            'title' => 'Overdue analytics CAPA',
            'description' => 'Seeded overdue CAPA for analytics bucket validation.',
            'assigned_to_id' => $jojo->id,
            'verified_by_id' => $jojo->id,
            'due_date' => now()->subDay()->toDateString(),
            'status' => 'Open',
        ]);

        $supplier = Supplier::create([
            'tenant_id' => $tenant->id,
            'name' => 'High Risk Packaging Supplier',
            'supplier_code' => 'SUP-RISK-99',
            'category' => 'Packaging',
            'approval_status' => 'Rejected',
            'risk_level' => 'High',
            'owner_id' => $jojo->id,
        ]);

        SupplierCertificate::create([
            'tenant_id' => $tenant->id,
            'supplier_id' => $supplier->id,
            'certificate_type' => 'Food contact declaration',
            'certificate_number' => 'FCD-EXP-99',
            'issued_at' => now()->subYear()->toDateString(),
            'expires_at' => now()->addDays(10)->toDateString(),
            'status' => 'Expiring',
        ]);

        $equipment = EquipmentAsset::create([
            'tenant_id' => $tenant->id,
            'asset_tag' => 'DET-FAIL-99',
            'name' => 'Detector challenge block',
            'location' => 'QA lab',
            'owner_id' => $jojo->id,
            'calibration_interval_days' => 90,
            'critical_to_food_safety' => true,
            'next_calibration_due_at' => now()->addDays(10)->toDateString(),
            'status' => 'Active',
        ]);

        CalibrationRecord::create([
            'tenant_id' => $tenant->id,
            'equipment_asset_id' => $equipment->id,
            'performed_by_id' => $jojo->id,
            'performed_at' => now()->toDateString(),
            'due_at' => now()->addDays(10)->toDateString(),
            'result' => 'Fail',
            'notes' => 'Failure added for analytics recalculation.',
        ]);

        $this->withToken($this->tokenFor($jojo))
            ->getJson("/api/tenants/{$tenant->slug}/analytics")
            ->assertOk()
            ->assertJsonPath('data.incident_trends.total', 2)
            ->assertJsonPath('data.incident_trends.open', 1)
            ->assertJsonPath('data.incident_trends.by_status.Open', 1)
            ->assertJsonPath('data.incident_trends.by_severity.Critical', 1)
            ->assertJsonPath('data.capa_ageing.open_total', 2)
            ->assertJsonPath('data.capa_ageing.buckets.overdue', 1)
            ->assertJsonPath('data.supplier_risk.suppliers_by_risk_level.High', 1)
            ->assertJsonPath('data.supplier_risk.suppliers_by_approval_status.Rejected', 1)
            ->assertJsonPath('data.supplier_risk.high_risk_suppliers', 1)
            ->assertJsonPath('data.supplier_risk.certificates_expiring_30_days', 1)
            ->assertJsonPath('data.supplier_risk.calibrations_due_30_days', 1)
            ->assertJsonPath('data.supplier_risk.calibration_failures', 1);
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
        return $user->createToken('phase-eleven-test')->plainTextToken;
    }
}
