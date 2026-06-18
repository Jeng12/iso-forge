<?php

namespace Tests\Feature;

use App\Models\CorrectiveAction;
use App\Models\CriticalControlPoint;
use App\Models\EmergencyDrill;
use App\Models\EmergencyResponsePlan;
use App\Models\IncidentAction;
use App\Models\IncidentReport;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseTenIncidentResponseTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_ten_incident_response_overview_returns_seeded_records_and_metrics(): void
    {
        $this->seed();

        $tenant = $this->tenant();
        $jojo = $this->user('jojo@iso-forge.test');

        $this->withToken($this->tokenFor($jojo))
            ->getJson("/api/tenants/{$tenant->slug}/incident-response")
            ->assertOk()
            ->assertJsonCount(1, 'data.incident_reports')
            ->assertJsonCount(1, 'data.actions')
            ->assertJsonCount(1, 'data.emergency_plans')
            ->assertJsonCount(1, 'data.emergency_drills')
            ->assertJsonPath('data.incident_reports.0.reference', 'IR-2026-0001')
            ->assertJsonPath('data.incident_reports.0.source_control.name', 'Pasteurization temperature')
            ->assertJsonPath('data.actions.0.status', 'Completed')
            ->assertJsonPath('data.emergency_plans.0.name', 'Pasteurization failure emergency response')
            ->assertJsonPath('data.emergency_drills.0.result', 'Effective');

        $this->withToken($this->tokenFor($jojo))
            ->getJson("/api/tenants/{$tenant->slug}/snapshot")
            ->assertOk()
            ->assertJsonPath('metrics.open_incidents', 0)
            ->assertJsonPath('metrics.emergency_plans', 1)
            ->assertJsonPath('metrics.emergency_drills', 1)
            ->assertJsonPath('metrics.incident_response_capas', 0)
            ->assertJsonPath('metrics.open_capas', 1);
    }

    public function test_phase_ten_incident_viewer_cannot_create_report(): void
    {
        $this->seed();

        $tenant = $this->tenant();
        $auditor = $this->user('auditor@iso-forge.test');

        $this->withToken($this->tokenFor($auditor))
            ->getJson("/api/tenants/{$tenant->slug}/incident-response")
            ->assertOk();

        $this->withToken($this->tokenFor($auditor))
            ->postJson("/api/tenants/{$tenant->slug}/incident-response/reports", [
                'reference' => 'IR-READ-ONLY',
                'title' => 'Read-only incident attempt',
                'incident_type' => 'Food Safety',
                'severity' => 'Minor',
                'detected_at' => now()->toDateTimeString(),
                'description' => 'Auditor should not be able to create incident records.',
            ])
            ->assertForbidden();
    }

    public function test_phase_ten_manager_can_create_critical_incident_action_and_capa(): void
    {
        $this->seed();

        $tenant = $this->tenant();
        $jojo = $this->user('jojo@iso-forge.test');
        $jono = $this->user('jono@iso-forge.test');
        $ccp = CriticalControlPoint::query()->where('tenant_id', $tenant->id)->firstOrFail();
        $token = $this->tokenFor($jojo);

        $reportResponse = $this->withToken($token)
            ->postJson("/api/tenants/{$tenant->slug}/incident-response/reports", [
                'reference' => 'IR-2026-0099',
                'title' => 'Pasteurization critical-limit breach',
                'incident_type' => 'Food Safety',
                'severity' => 'Critical',
                'reported_by_id' => $jono->id,
                'owner_id' => $jojo->id,
                'source_control_type' => 'ccp',
                'source_control_id' => $ccp->id,
                'detected_at' => now()->toDateTimeString(),
                'description' => 'Temperature dipped below the validated limit during the batch.',
                'immediate_containment' => 'Stopped line, held product, and notified QA lead.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.severity', 'Critical')
            ->assertJsonPath('data.corrective_action.status', 'Open');

        $actionResponse = $this->withToken($token)
            ->postJson("/api/tenants/{$tenant->slug}/incident-response/reports/{$reportResponse->json('data.id')}/actions", [
                'action_type' => 'Containment',
                'description' => 'Segregate affected lot and verify pasteurizer calibration.',
                'responsible_user_id' => $jono->id,
                'due_date' => now()->addDay()->toDateString(),
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'Open');

        $this->assertSame(2, IncidentReport::query()->where('tenant_id', $tenant->id)->count());
        $this->assertSame(2, IncidentAction::query()->where('tenant_id', $tenant->id)->count());
        $this->assertSame(2, CorrectiveAction::query()->where('tenant_id', $tenant->id)->whereNotIn('status', ['Closed', 'Verified'])->count());
        $this->assertDatabaseHas('incident_reports', [
            'id' => $reportResponse->json('data.id'),
            'source_control_type' => CriticalControlPoint::class,
            'source_control_id' => $ccp->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'incident_response.incident_capa.created',
            'auditable_id' => $reportResponse->json('data.corrective_action_id'),
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'incident_response.action.created',
            'auditable_id' => $actionResponse->json('data.id'),
        ]);
    }

    public function test_phase_ten_poor_emergency_drill_creates_capa_and_updates_plan_review(): void
    {
        $this->seed();

        $tenant = $this->tenant();
        $jojo = $this->user('jojo@iso-forge.test');
        $joto = $this->user('joto@iso-forge.test');
        $token = $this->tokenFor($jojo);
        $completedAt = now()->toDateString();
        $nextReview = now()->addDays(180)->toDateString();

        $planResponse = $this->withToken($token)
            ->postJson("/api/tenants/{$tenant->slug}/incident-response/emergency-plans", [
                'name' => 'Power-loss fill-room emergency response',
                'scenario' => 'Fill-room power loss during active product transfer.',
                'owner_id' => $joto->id,
                'review_frequency_days' => 180,
                'response_steps' => ['Stop transfer', 'Hold exposed product', 'Escalate maintenance response'],
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'Active');

        $drillResponse = $this->withToken($token)
            ->postJson("/api/tenants/{$tenant->slug}/incident-response/emergency-plans/{$planResponse->json('data.id')}/drills", [
                'facilitator_id' => $jojo->id,
                'scheduled_at' => now()->toDateString(),
                'completed_at' => $completedAt,
                'result' => 'Needs Improvement',
                'participants_count' => 4,
                'effectiveness_score' => 62,
                'scenario_notes' => 'Team took too long to identify hold boundaries.',
                'notes' => 'Revise escalation checklist and repeat drill.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.result', 'Needs Improvement')
            ->assertJsonPath('data.corrective_action.status', 'Open');

        $this->assertSame(2, EmergencyResponsePlan::query()->where('tenant_id', $tenant->id)->count());
        $this->assertSame(2, EmergencyDrill::query()->where('tenant_id', $tenant->id)->count());
        $updatedPlan = EmergencyResponsePlan::query()->findOrFail($planResponse->json('data.id'));
        $this->assertSame($completedAt, $updatedPlan->last_reviewed_at?->toDateString());
        $this->assertSame($nextReview, $updatedPlan->next_review_due_at?->toDateString());
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'incident_response.drill_capa.created',
            'auditable_id' => $drillResponse->json('data.corrective_action_id'),
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
        return $user->createToken('phase-ten-test')->plainTextToken;
    }
}
