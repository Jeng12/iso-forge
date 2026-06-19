<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveDocumentRequest;
use App\Http\Requests\PruneDocumentVersionRequest;
use App\Http\Requests\RequestDocumentApprovalRequest;
use App\Http\Requests\ReviewSupersededDocumentVersionRequest;
use App\Http\Requests\StoreDocumentRequest;
use App\Http\Requests\StoreDocumentVersionRequest;
use App\Http\Requests\UpdateDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\DocumentApproval;
use App\Models\DocumentVersion;
use App\Models\ElectronicSignature;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function approvals(Tenant $tenant): JsonResponse
    {
        return response()->json([
            'data' => DocumentApproval::query()
                ->with(['approver:id,name,email', 'documentVersion.document'])
                ->whereHas('documentVersion.document', fn ($query) => $query->where('tenant_id', $tenant->id))
                ->latest()
                ->get(),
        ]);
    }

    public function index(Tenant $tenant): JsonResponse
    {
        return response()->json([
            'data' => DocumentResource::collection(
                Document::query()
                    ->with(['owner:id,name,email,job_title', 'currentVersion.approvals.approver:id,name,email,job_title', 'versions.approvals.approver:id,name,email,job_title'])
                    ->where('tenant_id', $tenant->id)
                    ->latest()
                    ->get()
            ),
        ]);
    }

    public function store(StoreDocumentRequest $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validated();
        $fileData = $this->versionFileData(
            $tenant,
            $data['document_number'],
            $data['version_number'],
            $request->file('file'),
            $data,
        );

        $document = DB::transaction(function () use ($data, $fileData, $request, $tenant): Document {
            $document = Document::create([
                'tenant_id' => $tenant->id,
                'document_number' => $data['document_number'],
                'title' => $data['title'],
                'category' => $data['category'],
                'owner_id' => $data['owner_id'],
                'status' => 'Draft',
            ]);

            $version = DocumentVersion::create([
                'document_id' => $document->id,
                'version_number' => $data['version_number'],
                'file_path' => $fileData['file_path'],
                'mime_type' => $fileData['mime_type'],
                'file_size' => $fileData['file_size'],
                'retention_until' => $data['retention_until'] ?? now()->addYears(6)->toDateString(),
                'status' => 'Draft',
                'change_summary' => $data['change_summary'] ?? null,
            ]);

            $document->update(['current_version_id' => $version->id]);

            foreach ($data['approver_ids'] ?? [] as $approverId) {
                DocumentApproval::create([
                    'document_version_id' => $version->id,
                    'approver_id' => $approverId,
                    'status' => 'Pending',
                ]);
            }

            AuditLog::appendFor(
                $tenant->id,
                $request->user()->id,
                'document.created',
                Document::class,
                $document->id,
                [],
                $document->fresh(['currentVersion', 'owner'])->toArray(),
                $request->ip(),
                $request->userAgent(),
            );

            return $document;
        });

        return response()->json([
            'data' => new DocumentResource($document->load(['owner:id,name,email,job_title', 'currentVersion.approvals.approver:id,name,email,job_title', 'versions.approvals.approver:id,name,email,job_title'])),
        ], 201);
    }

    public function update(UpdateDocumentRequest $request, Tenant $tenant, Document $document): JsonResponse
    {
        abort_unless((int) $document->tenant_id === (int) $tenant->id, 404);

        $data = $request->validated();
        $oldValues = $document->toArray();

        $document->update($data);

        AuditLog::appendFor(
            $tenant->id,
            $request->user()->id,
            'document.updated',
            Document::class,
            $document->id,
            $oldValues,
            $document->fresh(['currentVersion', 'owner'])->toArray(),
            $request->ip(),
            $request->userAgent(),
        );

        return response()->json([
            'data' => new DocumentResource($document->fresh(['owner:id,name,email,job_title', 'currentVersion.approvals.approver:id,name,email,job_title', 'versions.approvals.approver:id,name,email,job_title'])),
        ]);
    }

    public function storeVersion(StoreDocumentVersionRequest $request, Tenant $tenant, Document $document): JsonResponse
    {
        abort_unless((int) $document->tenant_id === (int) $tenant->id, 404);

        $data = $request->validated();
        $fileData = $this->versionFileData(
            $tenant,
            $document->document_number,
            $data['version_number'],
            $request->file('file'),
            $data,
        );

        $document = DB::transaction(function () use ($data, $document, $fileData, $request, $tenant): Document {
            $previousVersion = $document->currentVersion;
            $version = DocumentVersion::create([
                'document_id' => $document->id,
                'version_number' => $data['version_number'],
                'file_path' => $fileData['file_path'],
                'mime_type' => $fileData['mime_type'],
                'file_size' => $fileData['file_size'],
                'retention_until' => $data['retention_until'] ?? now()->addYears(6)->toDateString(),
                'status' => 'Draft',
                'change_summary' => $data['change_summary'] ?? null,
            ]);

            $oldValues = $document->toArray();

            if ($previousVersion) {
                $previousVersion->update([
                    'status' => 'Superseded',
                    'superseded_at' => now(),
                    'superseded_by_id' => $version->id,
                    'retention_until' => $previousVersion->retention_until ?? now()->addYears(6)->toDateString(),
                ]);
            }

            $document->update([
                'current_version_id' => $version->id,
                'status' => 'Draft',
            ]);

            foreach ($data['approver_ids'] ?? [] as $approverId) {
                DocumentApproval::create([
                    'document_version_id' => $version->id,
                    'approver_id' => $approverId,
                    'status' => 'Pending',
                ]);
            }

            AuditLog::appendFor(
                $tenant->id,
                $request->user()->id,
                'document.version_created',
                DocumentVersion::class,
                $version->id,
                $oldValues,
                $document->fresh(['currentVersion', 'owner'])->toArray(),
                $request->ip(),
                $request->userAgent(),
            );

            return $document;
        });

        return response()->json([
            'data' => new DocumentResource($document->load(['owner:id,name,email,job_title', 'currentVersion.approvals.approver:id,name,email,job_title', 'versions.approvals.approver:id,name,email,job_title'])),
        ], 201);
    }

    public function downloadVersion(Tenant $tenant, Document $document, DocumentVersion $documentVersion): StreamedResponse
    {
        abort_unless((int) $document->tenant_id === (int) $tenant->id, 404);
        abort_unless((int) $documentVersion->document_id === (int) $document->id, 404);
        abort_unless(Storage::disk('local')->exists($documentVersion->file_path), 404, 'Stored file not found.');

        return Storage::disk('local')->download(
            $documentVersion->file_path,
            basename($documentVersion->file_path),
            ['Content-Type' => $documentVersion->mime_type ?? 'application/octet-stream'],
        );
    }

    public function reviewSupersededVersion(
        ReviewSupersededDocumentVersionRequest $request,
        Tenant $tenant,
        Document $document,
        DocumentVersion $documentVersion
    ): JsonResponse {
        abort_unless((int) $document->tenant_id === (int) $tenant->id, 404);
        abort_unless((int) $documentVersion->document_id === (int) $document->id, 404);
        abort_if((int) $document->current_version_id === (int) $documentVersion->id, 422, 'Current version cannot be reviewed as superseded.');
        abort_unless($documentVersion->status === 'Superseded', 422, 'Only superseded versions can be reviewed.');

        $oldValues = $documentVersion->toArray();
        $documentVersion->update([
            'superseded_reviewed_at' => now(),
            'superseded_reviewed_by_id' => $request->user()->id,
            'superseded_review_notes' => $request->validated('notes'),
        ]);

        AuditLog::appendFor(
            $tenant->id,
            $request->user()->id,
            'document.version_superseded_reviewed',
            DocumentVersion::class,
            $documentVersion->id,
            $oldValues,
            $documentVersion->fresh()->toArray(),
            $request->ip(),
            $request->userAgent(),
        );

        return response()->json([
            'data' => new DocumentResource($document->fresh(['owner:id,name,email,job_title', 'currentVersion.approvals.approver:id,name,email,job_title', 'versions.approvals.approver:id,name,email,job_title'])),
        ]);
    }

    public function pruneVersion(
        PruneDocumentVersionRequest $request,
        Tenant $tenant,
        Document $document,
        DocumentVersion $documentVersion
    ): JsonResponse {
        abort_unless((int) $document->tenant_id === (int) $tenant->id, 404);
        abort_unless((int) $documentVersion->document_id === (int) $document->id, 404);
        abort_if((int) $document->current_version_id === (int) $documentVersion->id, 422, 'Current version storage cannot be pruned.');
        abort_if($documentVersion->pruned_at, 422, 'Document version storage has already been pruned.');
        abort_if($documentVersion->retention_until && $documentVersion->retention_until->isFuture(), 422, 'Document version is still inside its retention period.');

        $data = $request->validated();
        $oldValues = $documentVersion->toArray();
        $stored = Storage::disk('local')->exists($documentVersion->file_path);

        if ($stored) {
            Storage::disk('local')->delete($documentVersion->file_path);
        }

        $documentVersion->update([
            'pruned_at' => now(),
            'pruned_by_id' => $request->user()->id,
            'prune_reason' => $data['reason'],
        ]);

        AuditLog::appendFor(
            $tenant->id,
            $request->user()->id,
            'document.version_pruned',
            DocumentVersion::class,
            $documentVersion->id,
            $oldValues,
            [
                ...$documentVersion->fresh()->toArray(),
                'stored_file_deleted' => $stored,
            ],
            $request->ip(),
            $request->userAgent(),
        );

        return response()->json([
            'data' => new DocumentResource($document->fresh(['owner:id,name,email,job_title', 'currentVersion.approvals.approver:id,name,email,job_title', 'versions.approvals.approver:id,name,email,job_title'])),
        ]);
    }

    public function approve(ApproveDocumentRequest $request, Tenant $tenant, DocumentApproval $documentApproval): JsonResponse
    {
        $data = $request->validated();

        $documentApproval->load('documentVersion.document');
        $document = $documentApproval->documentVersion->document;

        abort_unless((int) $document->tenant_id === (int) $tenant->id, 404);

        DB::transaction(function () use ($data, $document, $documentApproval, $request, $tenant): void {
            $version = $documentApproval->documentVersion;

            $oldValues = [
                'approval_status' => $documentApproval->status,
                'version_status' => $version->status,
                'document_status' => $document->status,
            ];

            $documentApproval->update([
                'status' => 'Approved',
                'comments' => $data['comments'] ?? $documentApproval->comments,
                'approved_at' => now(),
            ]);

            $version->update([
                'status' => 'Approved',
                'approved_by_id' => $request->user()->id,
                'approval_date' => now()->toDateString(),
                'effective_date' => $data['effective_date'] ?? now()->toDateString(),
            ]);

            $document->update([
                'status' => 'Approved',
                'current_version_id' => $version->id,
            ]);

            ElectronicSignature::sign(
                $version->fresh(),
                $request->user(),
                'Document approval',
                $data['reason'] ?? 'Approval through document control workflow',
            );

            AuditLog::appendFor(
                $tenant->id,
                $request->user()->id,
                'document.approved',
                DocumentVersion::class,
                $version->id,
                $oldValues,
                [
                    'approval_status' => 'Approved',
                    'version_status' => 'Approved',
                    'document_status' => 'Approved',
                    'approved_by' => $request->user()->id,
                ],
                $request->ip(),
                $request->userAgent(),
            );
        });

        return response()->json([
            'data' => $documentApproval->fresh([
                'approver:id,name,email',
                'documentVersion.document',
                'documentVersion.signatures.user:id,name,email',
            ]),
        ]);
    }

    public function requestApprovals(RequestDocumentApprovalRequest $request, Tenant $tenant, Document $document): JsonResponse
    {
        abort_unless((int) $document->tenant_id === (int) $tenant->id, 404);

        $data = $request->validated();

        $version = $document->currentVersion;
        abort_unless($version, 422, 'Document has no current version.');

        foreach ($data['approver_ids'] as $approverId) {
            DocumentApproval::firstOrCreate([
                'document_version_id' => $version->id,
                'approver_id' => $approverId,
            ], [
                'status' => 'Pending',
            ]);
        }

        $document->update(['status' => 'Under Review']);
        $version->update(['status' => 'Under Review']);

        AuditLog::appendFor(
            $tenant->id,
            $request->user()->id,
            'document.approval_requested',
            DocumentVersion::class,
            $version->id,
            [],
            ['approver_ids' => $data['approver_ids']],
            $request->ip(),
            $request->userAgent(),
        );

        return response()->json([
            'data' => $document->fresh(['currentVersion.approvals.approver:id,name,email']),
        ]);
    }

    private function versionFileData(Tenant $tenant, string $documentNumber, string $versionNumber, ?UploadedFile $file, array $data): array
    {
        if ($file) {
            return [
                'file_path' => $this->storeUploadedVersion($tenant, $documentNumber, $versionNumber, $file),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ];
        }

        return [
            'file_path' => $data['file_path'],
            'mime_type' => $data['mime_type'] ?? null,
            'file_size' => $data['file_size'] ?? null,
        ];
    }

    private function storeUploadedVersion(Tenant $tenant, string $documentNumber, string $versionNumber, UploadedFile $file): string
    {
        $directory = 'documents/'.$tenant->slug;
        $extension = $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin';
        $baseName = (string) Str::of($documentNumber.'-'.$versionNumber)
            ->replace(['.', '_'], '-')
            ->slug('-');
        $filename = $baseName.'.'.$extension;
        $counter = 2;

        while (Storage::disk('local')->exists($directory.'/'.$filename)) {
            $filename = $baseName.'-'.$counter.'.'.$extension;
            $counter++;
        }

        return $file->storeAs($directory, $filename, 'local');
    }
}
