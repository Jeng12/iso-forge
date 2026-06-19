<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAuditFindingRequest;
use App\Http\Requests\StoreAuditRequest;
use App\Http\Requests\StoreManagementReviewRequest;
use App\Http\Requests\StoreQualityObjectiveRequest;
use App\Http\Requests\UpdateAuditRequest;
use App\Http\Requests\UpdateQualityObjectiveRequest;
use App\Http\Resources\AuditFindingResource;
use App\Http\Resources\AuditResource;
use App\Http\Resources\ManagementReviewResource;
use App\Http\Resources\QualityObjectiveResource;
use App\Models\Audit;
use App\Models\AuditFinding;
use App\Models\AuditLog;
use App\Models\ManagementReview;
use App\Models\QualityObjective;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QmsController extends Controller
{
    public function overview(Tenant $tenant): JsonResponse
    {
        return response()->json([
            'data' => [
                'objectives' => QualityObjectiveResource::collection(
                    QualityObjective::query()
                        ->with('owner:id,name,email,job_title')
                        ->where('tenant_id', $tenant->id)
                        ->orderBy('due_date')
                        ->get()
                ),
                'audits' => AuditResource::collection(
                    Audit::query()
                        ->with(['leadAuditor:id,name,email,job_title', 'findings'])
                        ->where('tenant_id', $tenant->id)
                        ->orderByDesc('scheduled_date')
                        ->get()
                ),
                'findings' => AuditFindingResource::collection(
                    AuditFinding::query()
                        ->with(['audit:id,title', 'owner:id,name,email,job_title', 'nonConformance'])
                        ->where('tenant_id', $tenant->id)
                        ->latest()
                        ->get()
                ),
                'management_reviews' => ManagementReviewResource::collection(
                    ManagementReview::query()
                        ->with('chair:id,name,email,job_title')
                        ->where('tenant_id', $tenant->id)
                        ->orderByDesc('review_date')
                        ->get()
                ),
            ],
        ]);
    }

    public function storeObjective(StoreQualityObjectiveRequest $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validated();

        $objective = QualityObjective::create([
            ...$data,
            'tenant_id' => $tenant->id,
            'status' => $data['status'] ?? 'Active',
        ]);

        $this->audit($request, $tenant, 'qms.objective.created', QualityObjective::class, $objective->id, [], $objective->toArray());

        return response()->json(['data' => new QualityObjectiveResource($objective->load('owner:id,name,email,job_title'))], 201);
    }

    public function updateObjective(UpdateQualityObjectiveRequest $request, Tenant $tenant, QualityObjective $qualityObjective): JsonResponse
    {
        abort_unless((int) $qualityObjective->tenant_id === (int) $tenant->id, 404);

        $data = $request->validated();

        $oldValues = $qualityObjective->toArray();
        $qualityObjective->fill($data)->save();

        $this->audit($request, $tenant, 'qms.objective.updated', QualityObjective::class, $qualityObjective->id, $oldValues, $qualityObjective->fresh()->toArray());

        return response()->json(['data' => new QualityObjectiveResource($qualityObjective->fresh('owner:id,name,email,job_title'))]);
    }

    public function storeAudit(StoreAuditRequest $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validated();

        $audit = Audit::create([
            ...$data,
            'tenant_id' => $tenant->id,
            'status' => $data['status'] ?? 'Planned',
        ]);

        $this->audit($request, $tenant, 'qms.audit.created', Audit::class, $audit->id, [], $audit->toArray());

        return response()->json(['data' => new AuditResource($audit->load('leadAuditor:id,name,email,job_title'))], 201);
    }

    public function updateAudit(UpdateAuditRequest $request, Tenant $tenant, Audit $audit): JsonResponse
    {
        abort_unless((int) $audit->tenant_id === (int) $tenant->id, 404);

        $data = $request->validated();

        $oldValues = $audit->toArray();
        $audit->fill($data)->save();

        $this->audit($request, $tenant, 'qms.audit.updated', Audit::class, $audit->id, $oldValues, $audit->fresh()->toArray());

        return response()->json(['data' => new AuditResource($audit->fresh(['leadAuditor:id,name,email,job_title', 'findings']))]);
    }

    public function storeFinding(StoreAuditFindingRequest $request, Tenant $tenant, Audit $audit): JsonResponse
    {
        abort_unless((int) $audit->tenant_id === (int) $tenant->id, 404);

        $data = $request->validated();

        $finding = AuditFinding::create([
            ...$data,
            'tenant_id' => $tenant->id,
            'audit_id' => $audit->id,
            'status' => $data['status'] ?? 'Open',
        ]);

        $this->audit($request, $tenant, 'qms.audit_finding.created', AuditFinding::class, $finding->id, [], $finding->toArray());

        return response()->json([
            'data' => new AuditFindingResource($finding->load(['audit:id,title', 'owner:id,name,email,job_title', 'nonConformance'])),
        ], 201);
    }

    public function storeManagementReview(StoreManagementReviewRequest $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validated();

        $review = ManagementReview::create([
            ...$data,
            'tenant_id' => $tenant->id,
            'status' => $data['status'] ?? 'Planned',
        ]);

        $this->audit($request, $tenant, 'qms.management_review.created', ManagementReview::class, $review->id, [], $review->toArray());

        return response()->json(['data' => new ManagementReviewResource($review->load('chair:id,name,email,job_title'))], 201);
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
