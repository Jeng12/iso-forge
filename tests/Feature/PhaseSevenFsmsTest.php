<?php

namespace Tests\Feature;

use App\Models\CorrectiveAction;
use App\Models\CriticalControlPoint;
use App\Models\HaccpPlan;
use App\Models\HazardAnalysis;
use App\Models\MonitoringRecord;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseSevenFsmsTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_seven_fsms_overview_returns_seeded_haccp_records(): void
    {
        $this->seed();

        $tenant = $this->tenant();
        $jojo = $this->user('jojo@iso-forge.test');

        $this->withToken($this->tokenFor($jojo))
            ->getJson("/api/tenants/{$tenant->slug}/fsms")
            ->assertOk()
            ->assertJsonCount(1, 'data.haccp_plans')
            ->assertJsonCount(2, 'data.hazards')
            ->assertJsonCount(1, 'data.ccps')
            ->assertJsonCount(1, 'data.oprps')
            ->assertJsonCount(1, 'data.prps')
            ->assertJsonCount(1, 'data.monitoring_records')
            ->assertJsonPath('data.haccp_plans.0.name', 'Pasteurized Mango Juice HACCP Plan')
            ->assertJsonPath('data.hazards.0.risk_score', 15)
            ->assertJsonPath('data.ccps.0.critical_limit', '>=72 C for 15 seconds')
            ->assertJsonPath('data.monitoring_records.0.result', 'Pass');

        $this->withToken($this->tokenFor($jojo))
            ->getJson("/api/tenants/{$tenant->slug}/snapshot")
            ->assertOk()
            ->assertJsonPath('metrics.haccp_plans', 1)
            ->assertJsonPath('metrics.active_ccps', 1)
            ->assertJsonPath('metrics.active_oprps', 1)
            ->assertJsonPath('metrics.fsms_deviations', 0);
    }

    public function test_phase_seven_fsms_viewer_cannot_create_haccp_plan(): void
    {
        $this->seed();

        $tenant = $this->tenant();
        $auditor = $this->user('auditor@iso-forge.test');

        $this->withToken($this->tokenFor($auditor))
            ->getJson("/api/tenants/{$tenant->slug}/fsms")
            ->assertOk();

        $this->withToken($this->tokenFor($auditor))
            ->postJson("/api/tenants/{$tenant->slug}/fsms/haccp-plans", [
                'name' => 'Read-only HACCP Plan',
                'product' => 'Shelf-stable juice',
                'scope' => 'Should be rejected for read-only FSMS users.',
            ])
            ->assertForbidden();
    }

    public function test_phase_seven_fsms_manager_can_create_plan_step_hazard_and_ccp(): void
    {
        $this->seed();

        $tenant = $this->tenant();
        $jojo = $this->user('jojo@iso-forge.test');
        $token = $this->tokenFor($jojo);

        $planResponse = $this->withToken($token)
            ->postJson("/api/tenants/{$tenant->slug}/fsms/haccp-plans", [
                'name' => 'Ready-to-drink Tea HACCP Plan',
                'product' => 'Bottled Tea',
                'scope' => 'Brewing, blending, filtration, filling, and finished product release.',
                'owner_id' => $jojo->id,
                'effective_date' => now()->addWeeks(2)->toDateString(),
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'Draft');

        $stepResponse = $this->withToken($token)
            ->postJson("/api/tenants/{$tenant->slug}/fsms/haccp-plans/{$planResponse->json('data.id')}/steps", [
                'sequence' => 2,
                'name' => 'Thermal processing',
                'description' => 'Heat treatment after blending.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.sequence', 2);

        $hazardResponse = $this->withToken($token)
            ->postJson("/api/tenants/{$tenant->slug}/fsms/process-steps/{$stepResponse->json('data.id')}/hazards", [
                'hazard_type' => 'Biological',
                'hazard_description' => 'Microbial survival after insufficient heat treatment.',
                'likelihood' => 4,
                'severity' => 5,
                'control_measure' => 'Validated thermal processing critical limit.',
                'control_type' => 'CCP',
            ])
            ->assertCreated()
            ->assertJsonPath('data.risk_score', 20);

        $ccpResponse = $this->withToken($token)
            ->postJson("/api/tenants/{$tenant->slug}/fsms/hazards/{$hazardResponse->json('data.id')}/ccps", [
                'name' => 'Tea thermal process temperature',
                'critical_limit' => '>=85 C for 30 seconds',
                'monitoring_frequency' => 'Every batch',
                'responsible_user_id' => $jojo->id,
                'corrective_action_procedure' => 'Hold lot, reprocess when allowed, and open CAPA.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'Active');

        $this->assertSame(2, HaccpPlan::query()->where('tenant_id', $tenant->id)->count());
        $this->assertSame(3, HazardAnalysis::query()->where('tenant_id', $tenant->id)->count());
        $this->assertSame(2, CriticalControlPoint::query()->where('tenant_id', $tenant->id)->count());
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'fsms.ccp.created',
            'auditable_id' => $ccpResponse->json('data.id'),
        ]);
    }

    public function test_phase_seven_monitoring_deviation_creates_capa(): void
    {
        $this->seed();

        $tenant = $this->tenant();
        $jojo = $this->user('jojo@iso-forge.test');
        $ccp = CriticalControlPoint::query()
            ->where('tenant_id', $tenant->id)
            ->where('name', 'Pasteurization temperature')
            ->firstOrFail();

        $response = $this->withToken($this->tokenFor($jojo))
            ->postJson("/api/tenants/{$tenant->slug}/fsms/monitoring-records", [
                'monitorable_type' => 'ccp',
                'monitorable_id' => $ccp->id,
                'recorded_by_id' => $jojo->id,
                'measured_value' => 69.80,
                'unit' => 'C',
                'result' => 'Fail',
                'is_deviation' => true,
                'observed_at' => now()->toDateTimeString(),
                'notes' => 'Batch MJ-DEV-01 fell below the critical limit.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.is_deviation', true)
            ->assertJsonPath('data.corrective_action.status', 'Open');

        $actionId = $response->json('data.corrective_action_id');

        $this->assertNotNull($actionId);
        $this->assertSame(1, MonitoringRecord::query()->where('tenant_id', $tenant->id)->where('is_deviation', true)->count());
        $this->assertSame(2, CorrectiveAction::query()->where('tenant_id', $tenant->id)->whereNotIn('status', ['Closed', 'Verified'])->count());
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'fsms.deviation_capa.created',
            'auditable_id' => $actionId,
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
        return $user->createToken('phase-seven-test')->plainTextToken;
    }
}
