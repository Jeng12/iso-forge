<?php

namespace Tests\Feature;

use App\Models\ManagementReview;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseTwelveManagementReviewPacketTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_twelve_packet_index_returns_seeded_management_review_evidence_summary(): void
    {
        $this->seed();

        $tenant = $this->tenant();
        $jojo = $this->user('jojo@iso-forge.test');

        $this->withToken($this->tokenFor($jojo))
            ->getJson("/api/tenants/{$tenant->slug}/management-review-packets")
            ->assertOk()
            ->assertJsonCount(1, 'data.packets')
            ->assertJsonPath('data.packets.0.packet_id', 'MRP-ANGKOR-QUALITY-FOODS-0001')
            ->assertJsonPath('data.packets.0.title', 'Monthly QMS Leadership Review')
            ->assertJsonPath('data.evidence_summary.qms.objectives', 1)
            ->assertJsonPath('data.evidence_summary.qms.audits', 1)
            ->assertJsonPath('data.evidence_summary.training.records', 1)
            ->assertJsonPath('data.evidence_summary.incident_response.reports', 1)
            ->assertJsonPath('data.evidence_summary.supplier_quality.suppliers', 1)
            ->assertJsonPath('data.evidence_summary.capa.open_actions', 1)
            ->assertJsonPath('data.evidence_summary.audit_chain.events', 26);
    }

    public function test_phase_twelve_packet_detail_contains_exportable_evidence_and_hash(): void
    {
        $this->seed();

        $tenant = $this->tenant();
        $jojo = $this->user('jojo@iso-forge.test');
        $review = ManagementReview::query()->where('tenant_id', $tenant->id)->firstOrFail();

        $response = $this->withToken($this->tokenFor($jojo))
            ->getJson("/api/tenants/{$tenant->slug}/management-review-packets/{$review->id}")
            ->assertOk()
            ->assertJsonPath('data.format_version', '1.0')
            ->assertJsonPath('data.packet_id', 'MRP-ANGKOR-QUALITY-FOODS-0001')
            ->assertJsonPath('data.tenant.slug', 'angkor-quality-foods')
            ->assertJsonPath('data.management_review.title', 'Monthly QMS Leadership Review')
            ->assertJsonPath('data.qms.objectives.0.title', 'Reduce uncontrolled document use on production lines')
            ->assertJsonPath('data.training.recent_records.0.competency_status', 'Competent')
            ->assertJsonPath('data.incident_response.reports.0.reference', 'IR-2026-0001')
            ->assertJsonPath('data.supplier_quality.suppliers.0.supplier_code', 'SUP-MANGO-01')
            ->assertJsonPath('data.capa.open_actions.0.status', 'In Progress')
            ->assertJsonPath('data.audit_chain.events_count', 26);

        $this->assertSame(64, strlen($response->json('data.packet_hash')));
        $this->assertNotEmpty($response->json('data.audit_chain.latest_hash'));
    }

    public function test_phase_twelve_packet_download_returns_json_attachment(): void
    {
        $this->seed();

        $tenant = $this->tenant();
        $auditor = $this->user('auditor@iso-forge.test');
        $review = ManagementReview::query()->where('tenant_id', $tenant->id)->firstOrFail();

        $this->withToken($this->tokenFor($auditor))
            ->getJson("/api/tenants/{$tenant->slug}/management-review-packets/{$review->id}/download")
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="mrp-angkor-quality-foods-0001.json"')
            ->assertJsonPath('data.packet_id', 'MRP-ANGKOR-QUALITY-FOODS-0001')
            ->assertJsonPath('data.evidence_summary.audit_chain.events', 26);
    }

    public function test_phase_twelve_packet_permission_is_required(): void
    {
        $this->seed();

        $tenant = $this->tenant();
        $blockedUser = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Packet Blocked User',
            'email' => 'packet-blocked@iso-forge.test',
            'job_title' => 'Temporary Viewer',
            'password' => 'password',
        ]);

        $this->withToken($this->tokenFor($blockedUser))
            ->getJson("/api/tenants/{$tenant->slug}/management-review-packets")
            ->assertForbidden();
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
        return $user->createToken('phase-twelve-test')->plainTextToken;
    }
}
