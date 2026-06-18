<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\DocumentApproval;
use App\Models\DocumentVersion;
use App\Models\ElectronicSignature;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
            'data' => Document::query()
                ->with(['owner:id,name,email', 'currentVersion', 'versions.approvals.approver:id,name,email'])
                ->where('tenant_id', $tenant->id)
                ->latest()
                ->get(),
        ]);
    }

    public function store(Request $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validate([
            'document_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('documents')->where('tenant_id', $tenant->id),
            ],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'owner_id' => ['required', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'version_number' => ['required', 'string', 'max:50'],
            'file_path' => ['required', 'string', 'max:255'],
            'mime_type' => ['nullable', 'string', 'max:255'],
            'file_size' => ['nullable', 'integer', 'min:0'],
            'change_summary' => ['nullable', 'string'],
            'approver_ids' => ['nullable', 'array'],
            'approver_ids.*' => [Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
        ]);

        $document = DB::transaction(function () use ($data, $request, $tenant): Document {
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
                'file_path' => $data['file_path'],
                'mime_type' => $data['mime_type'] ?? null,
                'file_size' => $data['file_size'] ?? null,
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
            'data' => $document->load(['owner:id,name,email', 'currentVersion.approvals.approver:id,name,email']),
        ], 201);
    }

    public function approve(Request $request, Tenant $tenant, DocumentApproval $documentApproval): JsonResponse
    {
        $data = $request->validate([
            'comments' => ['nullable', 'string'],
            'effective_date' => ['nullable', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

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

    public function requestApprovals(Request $request, Tenant $tenant, Document $document): JsonResponse
    {
        abort_unless((int) $document->tenant_id === (int) $tenant->id, 404);

        $data = $request->validate([
            'approver_ids' => ['required', 'array', 'min:1'],
            'approver_ids.*' => [Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
        ]);

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
}
