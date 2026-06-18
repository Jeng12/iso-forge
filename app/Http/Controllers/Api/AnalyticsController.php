<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CalibrationRecord;
use App\Models\CorrectiveAction;
use App\Models\EquipmentAsset;
use App\Models\IncidentReport;
use App\Models\Supplier;
use App\Models\SupplierCertificate;
use App\Models\Tenant;
use App\Models\TrainingAssignment;
use App\Models\TrainingProgram;
use App\Models\TrainingRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class AnalyticsController extends Controller
{
    public function overview(Tenant $tenant): JsonResponse
    {
        return response()->json([
            'data' => [
                'generated_at' => now()->toISOString(),
                'incident_trends' => $this->incidentTrends($tenant),
                'capa_ageing' => $this->capaAgeing($tenant),
                'training_competency' => $this->trainingCompetency($tenant),
                'supplier_risk' => $this->supplierRisk($tenant),
            ],
        ]);
    }

    private function incidentTrends(Tenant $tenant): array
    {
        return [
            'total' => IncidentReport::query()->where('tenant_id', $tenant->id)->count(),
            'open' => IncidentReport::query()
                ->where('tenant_id', $tenant->id)
                ->whereNotIn('status', ['Closed', 'Contained'])
                ->count(),
            'by_status' => $this->countByField(IncidentReport::query()->where('tenant_id', $tenant->id), 'status'),
            'by_severity' => $this->countByField(IncidentReport::query()->where('tenant_id', $tenant->id), 'severity'),
            'recent' => IncidentReport::query()
                ->with(['owner:id,name,email', 'sourceControl'])
                ->where('tenant_id', $tenant->id)
                ->latest('detected_at')
                ->limit(5)
                ->get(['id', 'tenant_id', 'reference', 'title', 'severity', 'status', 'owner_id', 'source_control_type', 'source_control_id', 'detected_at']),
        ];
    }

    private function capaAgeing(Tenant $tenant): array
    {
        $today = now()->startOfDay();
        $openCapas = CorrectiveAction::query()
            ->with('assignee:id,name,email')
            ->where('tenant_id', $tenant->id)
            ->whereNotIn('status', ['Closed', 'Verified'])
            ->orderBy('due_date')
            ->get();

        $buckets = [
            'overdue' => 0,
            'due_next_7_days' => 0,
            'due_next_30_days' => 0,
            'future' => 0,
            'no_due_date' => 0,
        ];

        $items = $openCapas->map(function (CorrectiveAction $action) use (&$buckets, $today): array {
            $bucket = $this->capaBucket($action->due_date, $today);
            $buckets[$bucket]++;

            return [
                'id' => $action->id,
                'title' => $action->title,
                'status' => $action->status,
                'assignee' => $action->assignee,
                'due_date' => $action->due_date?->toDateString(),
                'age_days' => (int) $action->created_at?->startOfDay()->diffInDays($today),
                'bucket' => $bucket,
            ];
        })->values();

        return [
            'open_total' => $openCapas->count(),
            'buckets' => $buckets,
            'oldest_open_days' => $items->max('age_days') ?? 0,
            'items' => $items,
        ];
    }

    private function trainingCompetency(Tenant $tenant): array
    {
        $recordTotal = TrainingRecord::query()->where('tenant_id', $tenant->id)->count();
        $passCount = TrainingRecord::query()->where('tenant_id', $tenant->id)->where('result', 'Pass')->count();

        return [
            'programs' => TrainingProgram::query()->where('tenant_id', $tenant->id)->count(),
            'assignments_by_status' => $this->countByField(TrainingAssignment::query()->where('tenant_id', $tenant->id), 'status'),
            'records_by_result' => $this->countByField(TrainingRecord::query()->where('tenant_id', $tenant->id), 'result'),
            'records_by_competency_status' => $this->countByField(TrainingRecord::query()->where('tenant_id', $tenant->id), 'competency_status'),
            'pass_rate' => $recordTotal > 0 ? (int) round(($passCount / $recordTotal) * 100) : 0,
            'expiring_records_30_days' => TrainingRecord::query()
                ->where('tenant_id', $tenant->id)
                ->whereBetween('expires_at', [now()->toDateString(), now()->addDays(30)->toDateString()])
                ->count(),
        ];
    }

    private function supplierRisk(Tenant $tenant): array
    {
        return [
            'suppliers_by_risk_level' => $this->countByField(Supplier::query()->where('tenant_id', $tenant->id), 'risk_level'),
            'suppliers_by_approval_status' => $this->countByField(Supplier::query()->where('tenant_id', $tenant->id), 'approval_status'),
            'high_risk_suppliers' => Supplier::query()->where('tenant_id', $tenant->id)->where('risk_level', 'High')->count(),
            'certificates_expiring_30_days' => SupplierCertificate::query()
                ->where('tenant_id', $tenant->id)
                ->whereBetween('expires_at', [now()->toDateString(), now()->addDays(30)->toDateString()])
                ->count(),
            'calibrations_due_30_days' => EquipmentAsset::query()
                ->where('tenant_id', $tenant->id)
                ->whereBetween('next_calibration_due_at', [now()->toDateString(), now()->addDays(30)->toDateString()])
                ->count(),
            'calibration_failures' => CalibrationRecord::query()
                ->where('tenant_id', $tenant->id)
                ->whereIn('result', ['Fail', 'Overdue'])
                ->count(),
        ];
    }

    private function countByField(Builder $query, string $field): array
    {
        return $query
            ->get([$field])
            ->countBy(fn ($model) => $model->{$field} ?: 'Unspecified')
            ->all();
    }

    private function capaBucket(?Carbon $dueDate, Carbon $today): string
    {
        if (! $dueDate) {
            return 'no_due_date';
        }

        $dueDate = $dueDate->copy()->startOfDay();

        if ($dueDate->lt($today)) {
            return 'overdue';
        }

        if ($dueDate->lte($today->copy()->addDays(7))) {
            return 'due_next_7_days';
        }

        if ($dueDate->lte($today->copy()->addDays(30))) {
            return 'due_next_30_days';
        }

        return 'future';
    }
}
