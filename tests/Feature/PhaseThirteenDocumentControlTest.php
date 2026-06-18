<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\ManagementReview;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhaseThirteenDocumentControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_can_be_created_with_uploaded_file_and_downloaded(): void
    {
        Storage::fake('local');
        $this->seed();

        $tenant = $this->tenant();
        $jojo = $this->user('jojo@iso-forge.test');
        $joto = $this->user('joto@iso-forge.test');

        $response = $this->withToken($this->tokenFor($jojo))
            ->post("/api/tenants/{$tenant->slug}/documents", [
                'document_number' => 'QMS-UP-013',
                'title' => 'Uploaded Document Control Procedure',
                'category' => 'ISO 9001 Clause 7.5',
                'owner_id' => $joto->id,
                'version_number' => '1.0',
                'file' => UploadedFile::fake()->create('procedure.pdf', 24, 'application/pdf'),
                'change_summary' => 'Initial controlled upload.',
                'approver_ids' => [$jojo->id],
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.document_number', 'QMS-UP-013')
            ->assertJsonPath('data.current_version.version_number', '1.0');

        $filePath = $response->json('data.current_version.file_path');
        $this->assertStringStartsWith('documents/angkor-quality-foods/qms-up-013-1-0', $filePath);
        Storage::disk('local')->assertExists($filePath);

        $document = Document::query()->where('document_number', 'QMS-UP-013')->firstOrFail();
        $version = DocumentVersion::query()->findOrFail($document->current_version_id);

        $this->withToken($this->tokenFor($jojo))
            ->get("/api/tenants/{$tenant->slug}/documents/{$document->id}/versions/{$version->id}/download")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_document_metadata_and_new_uploaded_version_can_be_updated(): void
    {
        Storage::fake('local');
        $this->seed();

        $tenant = $this->tenant();
        $jojo = $this->user('jojo@iso-forge.test');
        $joto = $this->user('joto@iso-forge.test');
        $document = Document::query()->where('document_number', 'QMS-SOP-001')->firstOrFail();

        $this->withToken($this->tokenFor($jojo))
            ->patchJson("/api/tenants/{$tenant->slug}/documents/{$document->id}", [
                'title' => 'Document Control SOP Revised',
                'category' => 'ISO 9001 Clause 7.5.3',
                'owner_id' => $joto->id,
                'status' => 'Under Review',
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Document Control SOP Revised')
            ->assertJsonPath('data.status', 'Under Review');

        $versionResponse = $this->withToken($this->tokenFor($jojo))
            ->post("/api/tenants/{$tenant->slug}/documents/{$document->id}/versions", [
                'version_number' => '1.1',
                'file' => UploadedFile::fake()->create('document-control-1-1.pdf', 18, 'application/pdf'),
                'change_summary' => 'Added storage retention notes.',
                'approver_ids' => [$joto->id],
            ]);

        $versionResponse
            ->assertCreated()
            ->assertJsonPath('data.current_version.version_number', '1.1')
            ->assertJsonPath('data.status', 'Draft');

        Storage::disk('local')->assertExists($versionResponse->json('data.current_version.file_path'));

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'document.updated',
            'auditable_id' => $document->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'document.version_created',
            'auditable_id' => $versionResponse->json('data.current_version.id'),
        ]);
    }

    public function test_document_validation_contract_returns_field_errors(): void
    {
        $this->seed();

        $tenant = $this->tenant();
        $jojo = $this->user('jojo@iso-forge.test');

        $this->withToken($this->tokenFor($jojo))
            ->postJson("/api/tenants/{$tenant->slug}/documents", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'document_number',
                'title',
                'category',
                'owner_id',
                'version_number',
                'file_path',
            ]);
    }

    public function test_management_review_packet_can_render_as_pdf(): void
    {
        $this->seed();

        $tenant = $this->tenant();
        $auditor = $this->user('auditor@iso-forge.test');
        $review = ManagementReview::query()->where('tenant_id', $tenant->id)->firstOrFail();

        $response = $this->withToken($this->tokenFor($auditor))
            ->get("/api/tenants/{$tenant->slug}/management-review-packets/{$review->id}/pdf");

        $response
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'attachment; filename="mrp-angkor-quality-foods-'.str_pad((string) $review->id, 4, '0', STR_PAD_LEFT).'.pdf"');

        $this->assertStringStartsWith('%PDF-1.4', $response->baseResponse->getContent());
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
        return $user->createToken('phase-thirteen-test')->plainTextToken;
    }
}
