<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CorrectiveAction;
use App\Models\Document;
use App\Models\Risk;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IsoForgeController extends Controller
{
    public function snapshot(Request $request, Tenant $tenant): JsonResponse
    {
        $this->authorizeTenant($request, $tenant);

        return response()->json([
            'tenant' => $tenant,
            'metrics' => [
                'documents' => $tenant->documents()->count(),
                'approved_documents' => $tenant->documents()->where('status', 'Approved')->count(),
                'open_capas' => $tenant->correctiveActions()->whereNotIn('status', ['Closed', 'Verified'])->count(),
                'high_risks' => $tenant->risks()->where('risk_score', '>=', 15)->count(),
                'quality_objectives' => $tenant->qualityObjectives()->count(),
                'planned_audits' => $tenant->audits()->where('status', 'Planned')->count(),
                'open_findings' => $tenant->auditFindings()->whereNotIn('status', ['Closed', 'Verified'])->count(),
                'management_reviews' => $tenant->managementReviews()->count(),
                'haccp_plans' => $tenant->haccpPlans()->count(),
                'active_ccps' => $tenant->criticalControlPoints()->where('status', 'Active')->count(),
                'active_oprps' => $tenant->operationalPrerequisitePrograms()->where('status', 'Active')->count(),
                'fsms_deviations' => $tenant->monitoringRecords()->where('is_deviation', true)->count(),
                'approved_suppliers' => $tenant->suppliers()->where('approval_status', 'Approved')->count(),
                'supplier_certificates_expiring' => $tenant->supplierCertificates()->where('expires_at', '<=', now()->addDays(30)->toDateString())->count(),
                'critical_equipment' => $tenant->equipmentAssets()->where('critical_to_food_safety', true)->count(),
                'calibrations_due' => $tenant->equipmentAssets()->where('next_calibration_due_at', '<=', now()->addDays(30)->toDateString())->count(),
                'audit_events' => $tenant->auditLogs()->count(),
            ],
            'latest_audit_hash' => $tenant->auditLogs()->latest('id')->value('entry_hash'),
        ]);
    }

    public function users(Request $request, Tenant $tenant): JsonResponse
    {
        $this->authorizeTenant($request, $tenant);

        return response()->json([
            'data' => User::query()
                ->with('roles:id,name,slug')
                ->where('tenant_id', $tenant->id)
                ->orderBy('name')
                ->get(['id', 'tenant_id', 'name', 'email', 'job_title']),
        ]);
    }

    public function documents(Request $request, Tenant $tenant): JsonResponse
    {
        $this->authorizeTenant($request, $tenant);

        return response()->json([
            'data' => Document::query()
                ->with(['owner:id,name,email', 'currentVersion'])
                ->where('tenant_id', $tenant->id)
                ->latest()
                ->get(),
        ]);
    }

    public function risks(Request $request, Tenant $tenant): JsonResponse
    {
        $this->authorizeTenant($request, $tenant);

        return response()->json([
            'data' => Risk::query()
                ->with('owner:id,name,email')
                ->where('tenant_id', $tenant->id)
                ->orderByDesc('risk_score')
                ->get(),
        ]);
    }

    public function correctiveActions(Request $request, Tenant $tenant): JsonResponse
    {
        $this->authorizeTenant($request, $tenant);

        return response()->json([
            'data' => CorrectiveAction::query()
                ->with([
                    'assignee:id,name,email',
                    'verifier:id,name,email',
                    'nonConformance',
                    'risk',
                ])
                ->where('tenant_id', $tenant->id)
                ->orderBy('due_date')
                ->get(),
        ]);
    }

    public function auditLogs(Request $request, Tenant $tenant): JsonResponse
    {
        $this->authorizeTenant($request, $tenant);

        return response()->json([
            'data' => AuditLog::query()
                ->with('user:id,name,email')
                ->where('tenant_id', $tenant->id)
                ->latest('id')
                ->limit(50)
                ->get(),
        ]);
    }

    public function storeAuditLog(Request $request, Tenant $tenant): JsonResponse
    {
        $this->authorizeTenant($request, $tenant);

        $data = $request->validate([
            'event' => ['required', 'string', 'max:255'],
            'auditable_type' => ['required', 'string', 'max:255'],
            'auditable_id' => ['nullable', 'integer'],
            'old_values' => ['nullable', 'array'],
            'new_values' => ['nullable', 'array'],
        ]);

        $entry = AuditLog::appendFor(
            $tenant->id,
            $request->user()->id,
            $data['event'],
            $data['auditable_type'],
            $data['auditable_id'] ?? null,
            $data['old_values'] ?? [],
            $data['new_values'] ?? [],
            $request->ip(),
            $request->userAgent(),
        );

        return response()->json(['data' => $entry], 201);
    }

    private function authorizeTenant(Request $request, Tenant $tenant): void
    {
        abort_unless((int) $request->user()->tenant_id === (int) $tenant->id, 403, 'Tenant access denied.');
    }
}
