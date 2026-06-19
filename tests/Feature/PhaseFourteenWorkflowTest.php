<?php

namespace Tests\Feature;

use App\Models\Audit;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\EmergencyResponsePlan;
use App\Models\EquipmentAsset;
use App\Models\HaccpPlan;
use App\Models\IncidentReport;
use App\Models\ManagementReview;
use App\Models\QualityObjective;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\TrainingAssignment;
use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhaseFourteenWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_fourteen_module_edit_endpoints_use_contract_resources_and_audit_logs(): void
    {
        $this->seed();

        $tenant = $this->tenant();
        $jojo = $this->user('jojo@iso-forge.test');
        $joto = $this->user('joto@iso-forge.test');
        $role = Role::query()->where('tenant_id', $tenant->id)->where('slug', 'production-operator')->firstOrFail();
        $token = $this->tokenFor($jojo);

        $objective = QualityObjective::query()->where('tenant_id', $tenant->id)->firstOrFail();
        $this->withToken($token)
            ->patchJson("/api/tenants/{$tenant->slug}/qms/objectives/{$objective->id}", [
                'title' => 'Reduce uncontrolled document use on production lines',
                'measurement_method' => 'Monthly line clearance and binder audit',
                'target_value' => 97,
                'current_value' => 89,
                'owner_id' => $joto->id,
                'due_date' => now()->addMonth()->toDateString(),
                'status' => 'At Risk',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'At Risk')
            ->assertJsonPath('data.owner.id', $joto->id);

        $audit = Audit::query()->where('tenant_id', $tenant->id)->firstOrFail();
        $this->withToken($token)
            ->patchJson("/api/tenants/{$tenant->slug}/qms/audits/{$audit->id}", [
                'title' => 'Document Control Follow-up Audit',
                'scope' => 'Document control records and production-line access points.',
                'lead_auditor_id' => $jojo->id,
                'scheduled_date' => now()->addDays(10)->toDateString(),
                'status' => 'In Progress',
                'summary' => 'Follow-up audit opened from Phase 14 edit workflow.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'In Progress')
            ->assertJsonPath('data.lead_auditor.id', $jojo->id);

        $plan = HaccpPlan::query()->where('tenant_id', $tenant->id)->firstOrFail();
        $this->withToken($token)
            ->patchJson("/api/tenants/{$tenant->slug}/fsms/haccp-plans/{$plan->id}", [
                'name' => 'Mango puree pasteurization HACCP',
                'product' => 'Mango puree',
                'scope' => 'Receiving through chilled dispatch with revised verification checks.',
                'owner_id' => $jojo->id,
                'effective_date' => now()->toDateString(),
                'status' => 'Under Review',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'Under Review')
            ->assertJsonPath('data.owner.id', $jojo->id);

        $supplier = Supplier::query()->where('tenant_id', $tenant->id)->firstOrFail();
        $this->withToken($token)
            ->patchJson("/api/tenants/{$tenant->slug}/supplier-quality/suppliers/{$supplier->id}", [
                'name' => $supplier->name,
                'category' => $supplier->category,
                'contact_email' => 'qa-supplier@example.test',
                'owner_id' => $joto->id,
                'approval_status' => 'Conditional',
                'risk_level' => 'High',
                'approved_until' => now()->addDays(45)->toDateString(),
            ])
            ->assertOk()
            ->assertJsonPath('data.approval_status', 'Conditional')
            ->assertJsonPath('data.risk_level', 'High');

        $asset = EquipmentAsset::query()->where('tenant_id', $tenant->id)->firstOrFail();
        $this->withToken($token)
            ->patchJson("/api/tenants/{$tenant->slug}/supplier-quality/equipment/{$asset->id}", [
                'asset_tag' => $asset->asset_tag,
                'name' => $asset->name,
                'location' => 'Line 2 QA bench',
                'owner_id' => $joto->id,
                'calibration_interval_days' => 90,
                'critical_to_food_safety' => true,
                'status' => 'Hold',
            ])
            ->assertOk()
            ->assertJsonPath('data.location', 'Line 2 QA bench')
            ->assertJsonPath('data.status', 'Hold');

        $program = TrainingProgram::query()->where('tenant_id', $tenant->id)->firstOrFail();
        $this->withToken($token)
            ->patchJson("/api/tenants/{$tenant->slug}/training/programs/{$program->id}", [
                'code' => $program->code,
                'title' => 'Document control refresher',
                'iso_clause' => 'ISO 9001:2015 7.5',
                'delivery_method' => 'Workshop',
                'owner_id' => $jojo->id,
                'refresher_interval_days' => 180,
                'status' => 'Active',
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Document control refresher')
            ->assertJsonPath('data.refresher_interval_days', 180);

        $assignment = TrainingAssignment::query()->where('tenant_id', $tenant->id)->firstOrFail();
        $this->withToken($token)
            ->patchJson("/api/tenants/{$tenant->slug}/training/assignments/{$assignment->id}", [
                'user_id' => $joto->id,
                'required_for_role_id' => $role->id,
                'due_date' => now()->addDays(14)->toDateString(),
                'status' => 'Needs Coaching',
                'notes' => 'Refresh before next floor audit.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'Needs Coaching')
            ->assertJsonPath('data.required_for_role.id', $role->id);

        $incident = IncidentReport::query()->where('tenant_id', $tenant->id)->firstOrFail();
        $this->withToken($token)
            ->patchJson("/api/tenants/{$tenant->slug}/incident-response/reports/{$incident->id}", [
                'title' => 'Pasteurization deviation follow-up',
                'severity' => 'Major',
                'status' => 'Open',
                'owner_id' => $jojo->id,
                'detected_at' => now()->toISOString(),
                'description' => 'Follow-up opened to verify retained evidence.',
                'immediate_containment' => 'QA hold remains active pending record review.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'Open')
            ->assertJsonPath('data.severity', 'Major');

        $emergencyPlan = EmergencyResponsePlan::query()->where('tenant_id', $tenant->id)->firstOrFail();
        $this->withToken($token)
            ->patchJson("/api/tenants/{$tenant->slug}/incident-response/emergency-plans/{$emergencyPlan->id}", [
                'name' => $emergencyPlan->name,
                'scenario' => 'Pasteurization failure with expanded leadership escalation.',
                'owner_id' => $jojo->id,
                'review_frequency_days' => 180,
                'response_steps' => ['Hold product', 'Notify QA', 'Escalate maintenance', 'Release only after review'],
                'status' => 'Under Review',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'Under Review')
            ->assertJsonPath('data.response_steps.2', 'Escalate maintenance');

        foreach ([
            'qms.objective.updated',
            'qms.audit.updated',
            'fsms.haccp_plan.updated',
            'supplier_quality.supplier.updated',
            'supplier_quality.equipment.updated',
            'training.program.updated',
            'training.assignment.updated',
            'incident_response.report.updated',
            'incident_response.plan.updated',
        ] as $event) {
            $this->assertDatabaseHas('audit_logs', ['event' => $event]);
        }
    }

    public function test_phase_fourteen_document_superseded_versions_can_be_reviewed_and_pruned_after_retention(): void
    {
        Storage::fake('local');
        $this->seed();

        $tenant = $this->tenant();
        $jojo = $this->user('jojo@iso-forge.test');
        $joto = $this->user('joto@iso-forge.test');
        $token = $this->tokenFor($jojo);
        $expiredRetention = now()->subDay()->toDateString();

        $this->withToken($token)
            ->post("/api/tenants/{$tenant->slug}/documents", [
                'document_number' => 'QMS-RET-014',
                'title' => 'Retention Workflow Procedure',
                'category' => 'ISO 9001 Clause 7.5',
                'owner_id' => $joto->id,
                'version_number' => '1.0',
                'file' => UploadedFile::fake()->create('retention-v1.pdf', 12, 'application/pdf'),
                'retention_until' => $expiredRetention,
                'change_summary' => 'Initial retention workflow release.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.current_version.retention_until', $expiredRetention);

        $document = Document::query()->where('document_number', 'QMS-RET-014')->firstOrFail();
        $oldVersion = DocumentVersion::query()->findOrFail($document->current_version_id);
        Storage::disk('local')->assertExists($oldVersion->file_path);

        $this->withToken($token)
            ->post("/api/tenants/{$tenant->slug}/documents/{$document->id}/versions", [
                'version_number' => '2.0',
                'file' => UploadedFile::fake()->create('retention-v2.pdf', 14, 'application/pdf'),
                'retention_until' => now()->addYears(6)->toDateString(),
                'change_summary' => 'Supersedes the first storage-controlled release.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.current_version.version_number', '2.0');

        $oldVersion->refresh();
        $this->assertSame('Superseded', $oldVersion->status);
        $this->assertNotNull($oldVersion->superseded_at);
        $this->assertNotNull($oldVersion->superseded_by_id);

        $this->withToken($token)
            ->patchJson("/api/tenants/{$tenant->slug}/documents/{$document->id}/versions/{$oldVersion->id}/superseded-review", [
                'notes' => 'Obsolete copy reviewed before pruning.',
            ])
            ->assertOk();

        $oldVersion->refresh();
        $this->assertSame('Obsolete copy reviewed before pruning.', $oldVersion->superseded_review_notes);

        $this->withToken($token)
            ->postJson("/api/tenants/{$tenant->slug}/documents/{$document->id}/versions/{$oldVersion->id}/prune", [
                'reason' => 'Retention expired and replacement version is controlled.',
            ])
            ->assertOk();

        $oldVersion->refresh();
        $this->assertNotNull($oldVersion->superseded_reviewed_at);
        $this->assertNotNull($oldVersion->pruned_at);
        $this->assertSame('Retention expired and replacement version is controlled.', $oldVersion->prune_reason);
        Storage::disk('local')->assertMissing($oldVersion->file_path);

        $this->assertDatabaseHas('audit_logs', ['event' => 'document.version_superseded_reviewed']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'document.version_pruned']);
        $this->assertSame(2, DocumentVersion::query()->where('document_id', $document->id)->count());
    }

    public function test_phase_fourteen_packet_pdf_has_paginated_sections_and_signature_blocks(): void
    {
        $this->seed();

        $tenant = $this->tenant();
        $auditor = $this->user('auditor@iso-forge.test');
        $review = ManagementReview::query()->where('tenant_id', $tenant->id)->firstOrFail();

        $response = $this->withToken($this->tokenFor($auditor))
            ->get("/api/tenants/{$tenant->slug}/management-review-packets/{$review->id}/pdf")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $content = $response->baseResponse->getContent();

        $this->assertStringStartsWith('%PDF-1.4', $content);
        $this->assertStringContainsString('/Count ', $content);
        $this->assertStringContainsString('Signature Blocks', $content);
        $this->assertStringContainsString('QMS Objectives', $content);
        $this->assertStringContainsString('Latest Audit Chain Events', $content);
    }

    public function test_phase_fourteen_frontend_shell_renders_edit_and_retention_panels(): void
    {
        $this->get('/app')
            ->assertOk()
            ->assertSee('Superseded Versions')
            ->assertSee('Edit Objective')
            ->assertSee('Edit Audit')
            ->assertSee('Edit HACCP Plan')
            ->assertSee('Edit Supplier')
            ->assertSee('Edit Equipment')
            ->assertSee('Edit Program')
            ->assertSee('Edit Assignment')
            ->assertSee('Edit Incident')
            ->assertSee('Edit Emergency Plan');
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
        return $user->createToken('phase-fourteen-test')->plainTextToken;
    }
}
