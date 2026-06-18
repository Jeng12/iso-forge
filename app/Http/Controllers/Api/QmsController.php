<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Audit;
use App\Models\AuditFinding;
use App\Models\AuditLog;
use App\Models\ManagementReview;
use App\Models\QualityObjective;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QmsController extends Controller
{
    public function overview(Tenant $tenant): JsonResponse
    {
        return response()->json([
            'data' => [
                'objectives' => QualityObjective::query()
                    ->with('owner:id,name,email')
                    ->where('tenant_id', $tenant->id)
                    ->orderBy('due_date')
                    ->get(),
                'audits' => Audit::query()
                    ->with(['leadAuditor:id,name,email', 'findings'])
                    ->where('tenant_id', $tenant->id)
                    ->orderByDesc('scheduled_date')
                    ->get(),
                'findings' => AuditFinding::query()
                    ->with(['audit:id,title', 'owner:id,name,email', 'nonConformance'])
                    ->where('tenant_id', $tenant->id)
                    ->latest()
                    ->get(),
                'management_reviews' => ManagementReview::query()
                    ->with('chair:id,name,email')
                    ->where('tenant_id', $tenant->id)
                    ->orderByDesc('review_date')
                    ->get(),
            ],
        ]);
    }

    public function storeObjective(Request $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'iso_clause' => ['sometimes', 'string', 'max:255'],
            'baseline_value' => ['nullable', 'numeric'],
            'target_value' => ['required', 'numeric'],
            'current_value' => ['nullable', 'numeric'],
            'unit' => ['sometimes', 'string', 'max:50'],
            'measurement_method' => ['required', 'string', 'max:255'],
            'owner_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'due_date' => ['nullable', 'date'],
            'status' => ['sometimes', 'string', 'max:255'],
        ]);

        $objective = QualityObjective::create([
            ...$data,
            'tenant_id' => $tenant->id,
            'status' => $data['status'] ?? 'Active',
        ]);

        $this->audit($request, $tenant, 'qms.objective.created', QualityObjective::class, $objective->id, [], $objective->toArray());

        return response()->json(['data' => $objective->load('owner:id,name,email')], 201);
    }

    public function updateObjective(Request $request, Tenant $tenant, QualityObjective $qualityObjective): JsonResponse
    {
        abort_unless((int) $qualityObjective->tenant_id === (int) $tenant->id, 404);

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'baseline_value' => ['nullable', 'numeric'],
            'target_value' => ['sometimes', 'numeric'],
            'current_value' => ['nullable', 'numeric'],
            'measurement_method' => ['sometimes', 'string', 'max:255'],
            'owner_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'due_date' => ['nullable', 'date'],
            'status' => ['sometimes', 'string', 'max:255'],
        ]);

        $oldValues = $qualityObjective->toArray();
        $qualityObjective->fill($data)->save();

        $this->audit($request, $tenant, 'qms.objective.updated', QualityObjective::class, $qualityObjective->id, $oldValues, $qualityObjective->fresh()->toArray());

        return response()->json(['data' => $qualityObjective->fresh('owner:id,name,email')]);
    }

    public function storeAudit(Request $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'audit_type' => ['sometimes', 'string', 'max:255'],
            'iso_standard' => ['sometimes', 'string', 'max:255'],
            'scope' => ['required', 'string'],
            'lead_auditor_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'scheduled_date' => ['required', 'date'],
            'status' => ['sometimes', 'string', 'max:255'],
            'summary' => ['nullable', 'string'],
        ]);

        $audit = Audit::create([
            ...$data,
            'tenant_id' => $tenant->id,
            'status' => $data['status'] ?? 'Planned',
        ]);

        $this->audit($request, $tenant, 'qms.audit.created', Audit::class, $audit->id, [], $audit->toArray());

        return response()->json(['data' => $audit->load('leadAuditor:id,name,email')], 201);
    }

    public function updateAudit(Request $request, Tenant $tenant, Audit $audit): JsonResponse
    {
        abort_unless((int) $audit->tenant_id === (int) $tenant->id, 404);

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'scope' => ['sometimes', 'string'],
            'lead_auditor_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'scheduled_date' => ['sometimes', 'date'],
            'completed_at' => ['nullable', 'date'],
            'status' => ['sometimes', 'string', 'max:255'],
            'summary' => ['nullable', 'string'],
        ]);

        $oldValues = $audit->toArray();
        $audit->fill($data)->save();

        $this->audit($request, $tenant, 'qms.audit.updated', Audit::class, $audit->id, $oldValues, $audit->fresh()->toArray());

        return response()->json(['data' => $audit->fresh(['leadAuditor:id,name,email', 'findings'])]);
    }

    public function storeFinding(Request $request, Tenant $tenant, Audit $audit): JsonResponse
    {
        abort_unless((int) $audit->tenant_id === (int) $tenant->id, 404);

        $data = $request->validate([
            'reference' => [
                'required',
                'string',
                'max:255',
                Rule::unique('audit_findings')->where('tenant_id', $tenant->id),
            ],
            'non_conformance_id' => ['nullable', Rule::exists('non_conformances', 'id')->where('tenant_id', $tenant->id)],
            'iso_clause' => ['required', 'string', 'max:255'],
            'finding_type' => ['required', 'string', 'max:255'],
            'severity' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'evidence' => ['nullable', 'string'],
            'owner_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'due_date' => ['nullable', 'date'],
            'status' => ['sometimes', 'string', 'max:255'],
        ]);

        $finding = AuditFinding::create([
            ...$data,
            'tenant_id' => $tenant->id,
            'audit_id' => $audit->id,
            'status' => $data['status'] ?? 'Open',
        ]);

        $this->audit($request, $tenant, 'qms.audit_finding.created', AuditFinding::class, $finding->id, [], $finding->toArray());

        return response()->json([
            'data' => $finding->load(['audit:id,title', 'owner:id,name,email', 'nonConformance']),
        ], 201);
    }

    public function storeManagementReview(Request $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'review_date' => ['required', 'date'],
            'chair_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'inputs' => ['nullable', 'array'],
            'decisions' => ['nullable', 'array'],
            'actions' => ['nullable', 'array'],
            'status' => ['sometimes', 'string', 'max:255'],
        ]);

        $review = ManagementReview::create([
            ...$data,
            'tenant_id' => $tenant->id,
            'status' => $data['status'] ?? 'Planned',
        ]);

        $this->audit($request, $tenant, 'qms.management_review.created', ManagementReview::class, $review->id, [], $review->toArray());

        return response()->json(['data' => $review->load('chair:id,name,email')], 201);
    }

    private function audit(Request $request, Tenant $tenant, string $event, string $type, int $id, array $oldValues, array $newValues): void
    {
        AuditLog::appendFor(
            $tenant->id,
            $request->user()->id,
            $event,
            $type,
            $id,
            $oldValues,
            $newValues,
            $request->ip(),
            $request->userAgent(),
        );
    }
}
