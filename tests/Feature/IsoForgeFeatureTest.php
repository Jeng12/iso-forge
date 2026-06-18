<?php

namespace Tests\Feature;

use App\Models\Audit;
use App\Models\AuditLog;
use App\Models\CorrectiveAction;
use App\Models\Document;
use App\Models\DocumentApproval;
use App\Models\ElectronicSignature;
use App\Models\ManagementReview;
use App\Models\QualityObjective;
use App\Models\Risk;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkflowTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IsoForgeFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_seeded_compliance_operations(): void
    {
        $this->seed();

        $this->get('/')
            ->assertOk()
            ->assertSee('Compliance Operations')
            ->assertSee('QMS-SOP-001')
            ->assertSee('Immutable Audit Ledger');
    }

    public function test_phase_three_workspace_route_renders_frontend_shell(): void
    {
        $this->get('/app')
            ->assertOk()
            ->assertSee('ISO-Forge')
            ->assertSee('Compliance workspace')
            ->assertSee('Analytics')
            ->assertSee('CAPA Ageing')
            ->assertSee('New Document')
            ->assertSee('New Objective')
            ->assertSee('New HACCP Plan')
            ->assertSee('New Monitoring Record')
            ->assertSee('New Supplier')
            ->assertSee('New Calibration')
            ->assertSee('New Program')
            ->assertSee('Complete Training')
            ->assertSee('New Awareness')
            ->assertSee('New Incident')
            ->assertSee('New Emergency Plan')
            ->assertSee('New Drill')
            ->assertSee('New CAPA');
    }

    public function test_sanctum_login_can_access_tenant_snapshot(): void
    {
        $this->seed();

        $tenant = Tenant::query()->where('slug', 'angkor-quality-foods')->firstOrFail();

        $login = $this->postJson('/api/auth/login', [
            'email' => 'jojo@iso-forge.test',
            'password' => 'password',
        ]);

        $login->assertOk()->assertJsonStructure(['token', 'user']);

        $this->withToken($login->json('token'))
            ->getJson("/api/tenants/{$tenant->slug}/snapshot")
            ->assertOk()
            ->assertJsonPath('tenant.slug', 'angkor-quality-foods')
            ->assertJsonPath('metrics.open_capas', 1);
    }

    public function test_phase_three_frontend_data_endpoints_return_workspace_collections(): void
    {
        $this->seed();

        $tenant = $this->tenant();
        $jojo = $this->user('jojo@iso-forge.test');
        $token = $this->tokenFor($jojo);

        $this->withToken($token)
            ->getJson("/api/tenants/{$tenant->slug}/users")
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonPath('data.0.tenant_id', $tenant->id);

        $this->withToken($token)
            ->getJson("/api/tenants/{$tenant->slug}/document-approvals")
            ->assertOk()
            ->assertJsonPath('data.0.document_version.document.tenant_id', $tenant->id);

        $this->withToken($token)
            ->getJson("/api/tenants/{$tenant->slug}/workflow-tasks")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_phase_four_qms_overview_returns_seeded_iso_9001_records(): void
    {
        $this->seed();

        $tenant = $this->tenant();
        $jojo = $this->user('jojo@iso-forge.test');

        $this->withToken($this->tokenFor($jojo))
            ->getJson("/api/tenants/{$tenant->slug}/qms")
            ->assertOk()
            ->assertJsonCount(1, 'data.objectives')
            ->assertJsonCount(1, 'data.audits')
            ->assertJsonCount(1, 'data.findings')
            ->assertJsonCount(1, 'data.management_reviews')
            ->assertJsonPath('data.objectives.0.iso_clause', 'ISO 9001:2015 6.2')
            ->assertJsonPath('data.findings.0.reference', 'AF-2026-0001');
    }

    public function test_phase_four_qms_viewer_cannot_create_objective(): void
    {
        $this->seed();

        $tenant = $this->tenant();
        $auditor = $this->user('auditor@iso-forge.test');

        $this->withToken($this->tokenFor($auditor))
            ->getJson("/api/tenants/{$tenant->slug}/qms")
            ->assertOk();

        $this->withToken($this->tokenFor($auditor))
            ->postJson("/api/tenants/{$tenant->slug}/qms/objectives", [
                'title' => 'Improve supplier release accuracy',
                'target_value' => 98,
                'unit' => '%',
                'measurement_method' => 'Monthly supplier-release sampling',
            ])
            ->assertForbidden();
    }

    public function test_phase_four_qms_manager_can_create_objective_audit_finding_and_review(): void
    {
        $this->seed();

        $tenant = $this->tenant();
        $jojo = $this->user('jojo@iso-forge.test');
        $token = $this->tokenFor($jojo);

        $objectiveResponse = $this->withToken($token)
            ->postJson("/api/tenants/{$tenant->slug}/qms/objectives", [
                'title' => 'Improve on-time CAPA effectiveness checks',
                'baseline_value' => 72,
                'target_value' => 95,
                'current_value' => 81,
                'unit' => '%',
                'measurement_method' => 'Monthly CAPA verification report',
                'owner_id' => $jojo->id,
                'due_date' => now()->addMonths(2)->toDateString(),
            ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Improve on-time CAPA effectiveness checks')
            ->assertJsonPath('data.target_value', '95.00');

        $auditResponse = $this->withToken($token)
            ->postJson("/api/tenants/{$tenant->slug}/qms/audits", [
                'title' => 'Focused CAPA Process Audit',
                'scope' => 'CAPA closure, verification independence, and objective evidence.',
                'lead_auditor_id' => $jojo->id,
                'scheduled_date' => now()->addDays(12)->toDateString(),
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'Planned');

        $this->withToken($token)
            ->postJson("/api/tenants/{$tenant->slug}/qms/audits/{$auditResponse->json('data.id')}/findings", [
                'reference' => 'AF-2026-0099',
                'iso_clause' => 'ISO 9001:2015 10.2',
                'finding_type' => 'Opportunity',
                'severity' => 'Minor',
                'description' => 'Effectiveness evidence could be linked more directly to objective metrics.',
                'evidence' => 'Sampled CAPA record includes closure notes but no objective trend link.',
                'owner_id' => $jojo->id,
                'due_date' => now()->addDays(20)->toDateString(),
            ])
            ->assertCreated()
            ->assertJsonPath('data.reference', 'AF-2026-0099');

        $this->withToken($token)
            ->postJson("/api/tenants/{$tenant->slug}/qms/management-reviews", [
                'title' => 'CAPA Performance Leadership Review',
                'review_date' => now()->addDays(25)->toDateString(),
                'chair_id' => $jojo->id,
                'inputs' => ['capa_effectiveness' => 'On-time checks improved to 81%.'],
                'decisions' => ['increase_dashboard_review' => true],
                'actions' => [['owner' => 'Jojo ISO Lead', 'action' => 'Review CAPA trend monthly.']],
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'Planned');

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'qms.objective.created',
            'auditable_id' => $objectiveResponse->json('data.id'),
        ]);
        $this->assertSame(2, QualityObjective::query()->where('tenant_id', $tenant->id)->count());
        $this->assertSame(2, Audit::query()->where('tenant_id', $tenant->id)->count());
        $this->assertSame(2, ManagementReview::query()->where('tenant_id', $tenant->id)->count());
    }

    public function test_api_rejects_cross_tenant_access(): void
    {
        $this->seed();

        $otherTenant = Tenant::factory()->create();
        $user = User::query()->where('email', 'jojo@iso-forge.test')->firstOrFail();
        $token = $user->createToken('test-token')->plainTextToken;

        $this->withToken($token)
            ->getJson("/api/tenants/{$otherTenant->slug}/snapshot")
            ->assertForbidden();
    }

    public function test_document_can_be_created_and_approved_with_signature_and_audit_log(): void
    {
        $this->seed();

        $tenant = $this->tenant();
        $jojo = $this->user('jojo@iso-forge.test');
        $joto = $this->user('joto@iso-forge.test');
        $token = $this->tokenFor($jojo);

        $this->withToken($token)
            ->postJson("/api/tenants/{$tenant->slug}/documents", [
                'document_number' => 'QMS-POL-009',
                'title' => 'Supplier Quality Policy',
                'category' => 'ISO 9001 Clause 8.4',
                'owner_id' => $joto->id,
                'version_number' => '0.1',
                'file_path' => 'documents/qms-pol-009-draft.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 90000,
                'change_summary' => 'Initial supplier policy draft.',
                'approver_ids' => [$jojo->id],
            ])
            ->assertCreated()
            ->assertJsonPath('data.document_number', 'QMS-POL-009')
            ->assertJsonPath('data.status', 'Draft');

        $document = Document::query()->where('document_number', 'QMS-POL-009')->firstOrFail();
        $approval = DocumentApproval::query()
            ->where('document_version_id', $document->current_version_id)
            ->firstOrFail();

        $this->withToken($token)
            ->postJson("/api/tenants/{$tenant->slug}/document-approvals/{$approval->id}/approve", [
                'comments' => 'Ready for controlled release.',
                'reason' => 'Policy approval',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'Approved')
            ->assertJsonPath('data.document_version.status', 'Approved');

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'status' => 'Approved',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'document.approved',
            'auditable_id' => $document->current_version_id,
        ]);
        $this->assertSame(1, ElectronicSignature::query()->where('signable_id', $document->current_version_id)->count());
    }

    public function test_risk_api_rejects_missing_permission(): void
    {
        $this->seed();

        $tenant = $this->tenant();
        $operator = $this->user('jono@iso-forge.test');

        $this->withToken($this->tokenFor($operator))
            ->postJson("/api/tenants/{$tenant->slug}/risks", [
                'title' => 'Calibration certificates not reviewed before production release',
                'category' => 'Calibration',
                'likelihood' => 5,
                'severity' => 4,
            ])
            ->assertForbidden();
    }

    public function test_risk_api_calculates_scores_and_writes_audit_log(): void
    {
        $this->seed();

        $tenant = $this->tenant();
        $qualityLead = $this->user('jojo@iso-forge.test');

        $payload = [
            'title' => 'Calibration certificates not reviewed before production release',
            'category' => 'Calibration',
            'likelihood' => 5,
            'severity' => 4,
            'residual_likelihood' => 2,
            'residual_severity' => 3,
            'owner_id' => $qualityLead->id,
            'treatment_plan' => 'Block release until calibration evidence is reviewed.',
            'status' => 'Treatment Planned',
        ];

        $this->withToken($this->tokenFor($qualityLead))
            ->postJson("/api/tenants/{$tenant->slug}/risks", $payload)
            ->assertCreated()
            ->assertJsonPath('data.risk_score', 20)
            ->assertJsonPath('data.residual_score', 6);

        $risk = Risk::query()->where('title', $payload['title'])->firstOrFail();

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'risk.created',
            'auditable_id' => $risk->id,
        ]);
    }

    public function test_capa_creation_starts_workflow_task_and_assignee_can_complete_it(): void
    {
        $this->seed();

        $tenant = $this->tenant();
        $jojo = $this->user('jojo@iso-forge.test');
        $jono = $this->user('jono@iso-forge.test');
        $token = $this->tokenFor($jojo);

        $nonConformanceResponse = $this->withToken($token)
            ->postJson("/api/tenants/{$tenant->slug}/non-conformances", [
                'reference' => 'NC-2026-0099',
                'source' => 'Customer Complaint',
                'description' => 'Finished lot label did not match the approved label master.',
                'iso_clause' => 'ISO 9001:2015 8.5.1',
                'severity' => 'Major',
                'detected_at' => now()->toDateString(),
                'owner_id' => $jojo->id,
                'root_cause' => 'Label verification step was skipped during shift handover.',
            ])
            ->assertCreated();

        $capaResponse = $this->withToken($token)
            ->postJson("/api/tenants/{$tenant->slug}/corrective-actions", [
                'non_conformance_id' => $nonConformanceResponse->json('data.id'),
                'title' => 'Add label verification hold point',
                'description' => 'Require a second-person label check before release.',
                'assigned_to_id' => $jono->id,
                'verified_by_id' => $jojo->id,
                'due_date' => now()->addDays(5)->toDateString(),
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'Open');

        $action = CorrectiveAction::query()->findOrFail($capaResponse->json('data.id'));
        $task = WorkflowTask::query()
            ->whereHas('workflowInstance', fn ($query) => $query->where('model_id', $action->id))
            ->firstOrFail();

        $this->assertSame($jono->id, $task->assigned_to_id);

        $this->withToken($this->tokenFor($jono))
            ->postJson("/api/tenants/{$tenant->slug}/workflow-tasks/{$task->id}/complete", [
                'comments' => 'Root cause confirmed and action plan drafted.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'Completed');

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'workflow_task.completed',
            'auditable_id' => $task->id,
        ]);
    }

    public function test_assigned_verifier_can_complete_verification_task_without_capa_manager_role(): void
    {
        $this->seed();

        $tenant = $this->tenant();
        $auditor = $this->user('auditor@iso-forge.test');
        $task = WorkflowTask::query()
            ->where('assigned_to_id', $auditor->id)
            ->where('state', 'verification')
            ->firstOrFail();

        $this->withToken($this->tokenFor($auditor))
            ->postJson("/api/tenants/{$tenant->slug}/workflow-tasks/{$task->id}/complete", [
                'comments' => 'Evidence reviewed; action is effective.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'Completed');

        $this->assertDatabaseHas('workflow_instances', [
            'id' => $task->workflow_instance_id,
            'status' => 'Closed',
            'current_state' => 'closed',
        ]);
        $this->assertDatabaseHas('corrective_actions', [
            'status' => 'Verified',
        ]);
    }

    public function test_audit_hash_chain_extends_after_phase_two_write(): void
    {
        $this->seed();

        $tenant = $this->tenant();
        $jojo = $this->user('jojo@iso-forge.test');
        $lastHash = AuditLog::query()->latest('id')->value('entry_hash');

        $this->withToken($this->tokenFor($jojo))
            ->postJson("/api/tenants/{$tenant->slug}/risks", [
                'title' => 'Incoming inspection evidence is incomplete',
                'category' => 'Inspection',
                'likelihood' => 3,
                'severity' => 4,
                'owner_id' => $jojo->id,
            ])
            ->assertCreated();

        $newLog = AuditLog::query()->latest('id')->firstOrFail();

        $this->assertSame('risk.created', $newLog->event);
        $this->assertSame($lastHash, $newLog->previous_hash);
        $this->assertNotSame($lastHash, $newLog->entry_hash);
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
        return $user->createToken('feature-test')->plainTextToken;
    }
}
