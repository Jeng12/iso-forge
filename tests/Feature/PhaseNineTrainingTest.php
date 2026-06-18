<?php

namespace Tests\Feature;

use App\Models\AwarenessAcknowledgement;
use App\Models\CompetencyRequirement;
use App\Models\CorrectiveAction;
use App\Models\Document;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\TrainingAssignment;
use App\Models\TrainingProgram;
use App\Models\TrainingRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseNineTrainingTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_nine_training_overview_returns_seeded_competency_records(): void
    {
        $this->seed();

        $tenant = $this->tenant();
        $jojo = $this->user('jojo@iso-forge.test');

        $this->withToken($this->tokenFor($jojo))
            ->getJson("/api/tenants/{$tenant->slug}/training")
            ->assertOk()
            ->assertJsonCount(1, 'data.programs')
            ->assertJsonCount(1, 'data.requirements')
            ->assertJsonCount(1, 'data.assignments')
            ->assertJsonCount(1, 'data.records')
            ->assertJsonCount(1, 'data.awareness_acknowledgements')
            ->assertJsonPath('data.programs.0.code', 'TRN-CCP-001')
            ->assertJsonPath('data.requirements.0.competency_area', 'Food-safety critical monitoring')
            ->assertJsonPath('data.assignments.0.status', 'Completed')
            ->assertJsonPath('data.records.0.competency_status', 'Competent')
            ->assertJsonPath('data.awareness_acknowledgements.0.status', 'Acknowledged');

        $this->withToken($this->tokenFor($jojo))
            ->getJson("/api/tenants/{$tenant->slug}/snapshot")
            ->assertOk()
            ->assertJsonPath('metrics.training_programs', 1)
            ->assertJsonPath('metrics.open_training_assignments', 0)
            ->assertJsonPath('metrics.competent_records', 1)
            ->assertJsonPath('metrics.awareness_acknowledgements', 1);
    }

    public function test_phase_nine_training_viewer_cannot_create_program(): void
    {
        $this->seed();

        $tenant = $this->tenant();
        $auditor = $this->user('auditor@iso-forge.test');

        $this->withToken($this->tokenFor($auditor))
            ->getJson("/api/tenants/{$tenant->slug}/training")
            ->assertOk();

        $this->withToken($this->tokenFor($auditor))
            ->postJson("/api/tenants/{$tenant->slug}/training/programs", [
                'code' => 'TRN-READ-ONLY',
                'title' => 'Read-only Training',
            ])
            ->assertForbidden();
    }

    public function test_phase_nine_manager_can_create_requirement_assignment_record_and_awareness(): void
    {
        $this->seed();

        $tenant = $this->tenant();
        $jojo = $this->user('jojo@iso-forge.test');
        $joto = $this->user('joto@iso-forge.test');
        $qualityRole = Role::query()->where('tenant_id', $tenant->id)->where('slug', 'quality-manager')->firstOrFail();
        $document = Document::query()->where('tenant_id', $tenant->id)->where('status', 'Approved')->firstOrFail();
        $token = $this->tokenFor($jojo);

        $programResponse = $this->withToken($token)
            ->postJson("/api/tenants/{$tenant->slug}/training/programs", [
                'code' => 'TRN-AUDIT-001',
                'title' => 'Internal audit evidence review',
                'iso_clause' => 'ISO 9001:2015 7.2',
                'delivery_method' => 'Workshop',
                'owner_id' => $joto->id,
                'refresher_interval_days' => 365,
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'Active');

        $requirementResponse = $this->withToken($token)
            ->postJson("/api/tenants/{$tenant->slug}/training/requirements", [
                'role_id' => $qualityRole->id,
                'training_program_id' => $programResponse->json('data.id'),
                'competency_area' => 'Evidence-based auditing',
                'required_level' => 'Qualified',
                'assessment_method' => 'Audit packet review',
                'due_within_days' => 45,
            ])
            ->assertCreated()
            ->assertJsonPath('data.role.slug', 'quality-manager');

        $assignmentResponse = $this->withToken($token)
            ->postJson("/api/tenants/{$tenant->slug}/training/programs/{$programResponse->json('data.id')}/assignments", [
                'user_id' => $joto->id,
                'assigned_by_id' => $jojo->id,
                'required_for_role_id' => $qualityRole->id,
                'due_date' => now()->addDays(20)->toDateString(),
                'notes' => 'Quality manager audit refresh.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'Assigned');

        $recordResponse = $this->withToken($token)
            ->postJson("/api/tenants/{$tenant->slug}/training/assignments/{$assignmentResponse->json('data.id')}/records", [
                'trainer_id' => $jojo->id,
                'evidence_document_id' => $document->id,
                'completed_at' => now()->toDateString(),
                'score' => 91,
                'result' => 'Pass',
                'competency_status' => 'Competent',
                'expires_at' => now()->addYear()->toDateString(),
                'notes' => 'Audit evidence review completed.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.result', 'Pass')
            ->assertJsonPath('data.competency_status', 'Competent');

        $awarenessResponse = $this->withToken($token)
            ->postJson("/api/tenants/{$tenant->slug}/training/awareness-acknowledgements", [
                'document_id' => $document->id,
                'user_id' => $jojo->id,
                'acknowledged_by_id' => $jojo->id,
                'acknowledged_at' => now()->toDateTimeString(),
                'statement' => 'Reviewed approved QMS procedure for awareness evidence.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'Acknowledged');

        $this->assertSame(2, TrainingProgram::query()->where('tenant_id', $tenant->id)->count());
        $this->assertSame(2, CompetencyRequirement::query()->where('tenant_id', $tenant->id)->count());
        $this->assertSame(2, TrainingAssignment::query()->where('tenant_id', $tenant->id)->count());
        $this->assertSame(2, TrainingRecord::query()->where('tenant_id', $tenant->id)->count());
        $this->assertSame(2, AwarenessAcknowledgement::query()->where('tenant_id', $tenant->id)->count());
        $this->assertDatabaseHas('training_assignments', [
            'id' => $assignmentResponse->json('data.id'),
            'status' => 'Completed',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'training.requirement.created',
            'auditable_id' => $requirementResponse->json('data.id'),
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'training.record.created',
            'auditable_id' => $recordResponse->json('data.id'),
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'training.awareness.created',
            'auditable_id' => $awarenessResponse->json('data.id'),
        ]);
    }

    public function test_phase_nine_failed_training_record_creates_competency_capa(): void
    {
        $this->seed();

        $tenant = $this->tenant();
        $jojo = $this->user('jojo@iso-forge.test');
        $jono = $this->user('jono@iso-forge.test');
        $program = TrainingProgram::query()->where('tenant_id', $tenant->id)->firstOrFail();
        $token = $this->tokenFor($jojo);

        $assignmentResponse = $this->withToken($token)
            ->postJson("/api/tenants/{$tenant->slug}/training/programs/{$program->id}/assignments", [
                'user_id' => $jono->id,
                'assigned_by_id' => $jojo->id,
                'due_date' => now()->addDays(7)->toDateString(),
                'notes' => 'Follow-up practical observation.',
            ])
            ->assertCreated();

        $recordResponse = $this->withToken($token)
            ->postJson("/api/tenants/{$tenant->slug}/training/assignments/{$assignmentResponse->json('data.id')}/records", [
                'trainer_id' => $jojo->id,
                'completed_at' => now()->toDateString(),
                'score' => 54,
                'result' => 'Fail',
                'competency_status' => 'Needs Coaching',
                'notes' => 'Operator missed escalation criteria during practical check.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.result', 'Fail')
            ->assertJsonPath('data.competency_status', 'Needs Coaching')
            ->assertJsonPath('data.corrective_action.status', 'Open');

        $this->assertSame('Needs Coaching', TrainingAssignment::query()->findOrFail($assignmentResponse->json('data.id'))->status);
        $this->assertSame(1, TrainingRecord::query()->where('tenant_id', $tenant->id)->where('result', 'Fail')->count());
        $this->assertSame(2, CorrectiveAction::query()->where('tenant_id', $tenant->id)->whereNotIn('status', ['Closed', 'Verified'])->count());
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'training.competency_capa.created',
            'auditable_id' => $recordResponse->json('data.corrective_action_id'),
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
        return $user->createToken('phase-nine-test')->plainTextToken;
    }
}
