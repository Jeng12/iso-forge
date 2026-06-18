<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PhaseFiveRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_api_requests_are_rejected(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();
    }

    public function test_cross_tenant_user_reference_is_rejected_by_validation(): void
    {
        $this->seed();

        $tenant = Tenant::query()->where('slug', 'angkor-quality-foods')->firstOrFail();
        $otherTenant = Tenant::factory()->create();
        $otherUser = User::factory()->create(['tenant_id' => $otherTenant->id]);
        $jojo = User::query()->where('email', 'jojo@iso-forge.test')->firstOrFail();
        $token = $jojo->createToken('phase-five-test')->plainTextToken;

        $this->withToken($token)
            ->postJson("/api/tenants/{$tenant->slug}/qms/objectives", [
                'title' => 'Invalid cross-tenant objective',
                'target_value' => 95,
                'unit' => '%',
                'measurement_method' => 'Monthly review',
                'owner_id' => $otherUser->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('owner_id');
    }

    public function test_audit_chain_verifier_passes_for_seeded_ledger(): void
    {
        $this->seed();

        $this->artisan('iso-forge:verify-audit-chain')
            ->assertExitCode(0);
    }

    public function test_audit_chain_verifier_detects_payload_tampering(): void
    {
        $this->seed();

        $log = AuditLog::query()->whereNotNull('payload_snapshot')->firstOrFail();
        $newValues = $log->new_values;
        $newValues['tampered'] = true;

        DB::table('audit_logs')
            ->where('id', $log->id)
            ->update(['new_values' => json_encode($newValues)]);

        $this->artisan('iso-forge:verify-audit-chain')
            ->assertExitCode(1);
    }

    public function test_audit_payload_hash_is_stable_when_json_keys_are_reordered(): void
    {
        $payload = [
            'tenant_id' => 1,
            'user_id' => 1,
            'event' => 'tenant.created',
            'auditable_type' => Tenant::class,
            'auditable_id' => 1,
            'old_values' => [],
            'new_values' => [
                'name' => 'Angkor Quality Foods',
                'slug' => 'angkor-quality-foods',
            ],
            'occurred_at' => now()->toJSON(),
        ];

        $reorderedPayload = [
            'event' => $payload['event'],
            'user_id' => $payload['user_id'],
            'tenant_id' => $payload['tenant_id'],
            'new_values' => [
                'slug' => 'angkor-quality-foods',
                'name' => 'Angkor Quality Foods',
            ],
            'old_values' => [],
            'occurred_at' => $payload['occurred_at'],
            'auditable_id' => $payload['auditable_id'],
            'auditable_type' => $payload['auditable_type'],
        ];

        $this->assertSame(AuditLog::payloadHash($payload), AuditLog::payloadHash($reorderedPayload));
    }
}
