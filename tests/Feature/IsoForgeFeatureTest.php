<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
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
}
