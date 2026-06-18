<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CorrectiveAction;
use App\Models\CriticalControlPoint;
use App\Models\HaccpPlan;
use App\Models\HazardAnalysis;
use App\Models\MonitoringRecord;
use App\Models\OperationalPrerequisiteProgram;
use App\Models\PrerequisiteProgram;
use App\Models\ProcessStep;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FsmsController extends Controller
{
    public function overview(Tenant $tenant): JsonResponse
    {
        return response()->json([
            'data' => [
                'haccp_plans' => HaccpPlan::query()
                    ->with(['owner:id,name,email', 'processSteps.hazardAnalyses.ccp', 'processSteps.hazardAnalyses.oprp'])
                    ->where('tenant_id', $tenant->id)
                    ->latest()
                    ->get(),
                'hazards' => HazardAnalysis::query()
                    ->with('processStep.haccpPlan:id,name,product')
                    ->where('tenant_id', $tenant->id)
                    ->orderByDesc('risk_score')
                    ->get(),
                'ccps' => CriticalControlPoint::query()
                    ->with(['hazardAnalysis.processStep.haccpPlan:id,name,product', 'responsibleUser:id,name,email'])
                    ->where('tenant_id', $tenant->id)
                    ->get(),
                'oprps' => OperationalPrerequisiteProgram::query()
                    ->with(['hazardAnalysis.processStep.haccpPlan:id,name,product', 'responsibleUser:id,name,email'])
                    ->where('tenant_id', $tenant->id)
                    ->get(),
                'prps' => PrerequisiteProgram::query()
                    ->with('owner:id,name,email')
                    ->where('tenant_id', $tenant->id)
                    ->get(),
                'monitoring_records' => MonitoringRecord::query()
                    ->with(['monitorable', 'recorder:id,name,email', 'correctiveAction:id,title,status'])
                    ->where('tenant_id', $tenant->id)
                    ->latest('observed_at')
                    ->limit(50)
                    ->get(),
            ],
        ]);
    }

    public function storePlan(Request $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'product' => ['required', 'string', 'max:255'],
            'scope' => ['required', 'string'],
            'owner_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'effective_date' => ['nullable', 'date'],
            'status' => ['sometimes', 'string', 'max:255'],
        ]);

        $plan = HaccpPlan::create([
            ...$data,
            'tenant_id' => $tenant->id,
            'status' => $data['status'] ?? 'Draft',
        ]);

        $this->audit($request, $tenant, 'fsms.haccp_plan.created', HaccpPlan::class, $plan->id, [], $plan->toArray());

        return response()->json(['data' => $plan->load('owner:id,name,email')], 201);
    }

    public function storeStep(Request $request, Tenant $tenant, HaccpPlan $haccpPlan): JsonResponse
    {
        abort_unless((int) $haccpPlan->tenant_id === (int) $tenant->id, 404);

        $data = $request->validate([
            'sequence' => ['required', 'integer', 'min:1'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $step = ProcessStep::create([
            ...$data,
            'tenant_id' => $tenant->id,
            'haccp_plan_id' => $haccpPlan->id,
        ]);

        $this->audit($request, $tenant, 'fsms.process_step.created', ProcessStep::class, $step->id, [], $step->toArray());

        return response()->json(['data' => $step], 201);
    }

    public function storeHazard(Request $request, Tenant $tenant, ProcessStep $processStep): JsonResponse
    {
        abort_unless((int) $processStep->tenant_id === (int) $tenant->id, 404);

        $data = $request->validate([
            'hazard_type' => ['required', 'string', 'max:255'],
            'hazard_description' => ['required', 'string'],
            'likelihood' => ['required', 'integer', 'min:1', 'max:5'],
            'severity' => ['required', 'integer', 'min:1', 'max:5'],
            'control_measure' => ['required', 'string'],
            'control_type' => ['required', 'in:CCP,OPRP,PRP,None'],
            'status' => ['sometimes', 'string', 'max:255'],
        ]);

        $hazard = HazardAnalysis::create([
            ...$data,
            'tenant_id' => $tenant->id,
            'process_step_id' => $processStep->id,
            'status' => $data['status'] ?? 'Assessed',
        ]);

        $this->audit($request, $tenant, 'fsms.hazard.created', HazardAnalysis::class, $hazard->id, [], $hazard->toArray());

        return response()->json(['data' => $hazard->load('processStep.haccpPlan:id,name,product')], 201);
    }

    public function storeCcp(Request $request, Tenant $tenant, HazardAnalysis $hazardAnalysis): JsonResponse
    {
        abort_unless((int) $hazardAnalysis->tenant_id === (int) $tenant->id, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'critical_limit' => ['required', 'string', 'max:255'],
            'monitoring_frequency' => ['required', 'string', 'max:255'],
            'responsible_user_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'corrective_action_procedure' => ['required', 'string'],
            'status' => ['sometimes', 'string', 'max:255'],
        ]);

        $ccp = CriticalControlPoint::create([
            ...$data,
            'tenant_id' => $tenant->id,
            'hazard_analysis_id' => $hazardAnalysis->id,
            'status' => $data['status'] ?? 'Active',
        ]);

        $hazardAnalysis->update(['control_type' => 'CCP']);
        $this->audit($request, $tenant, 'fsms.ccp.created', CriticalControlPoint::class, $ccp->id, [], $ccp->toArray());

        return response()->json(['data' => $ccp->load('responsibleUser:id,name,email')], 201);
    }

    public function storeOprp(Request $request, Tenant $tenant, HazardAnalysis $hazardAnalysis): JsonResponse
    {
        abort_unless((int) $hazardAnalysis->tenant_id === (int) $tenant->id, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'control_measure' => ['required', 'string'],
            'monitoring_frequency' => ['required', 'string', 'max:255'],
            'responsible_user_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'status' => ['sometimes', 'string', 'max:255'],
        ]);

        $oprp = OperationalPrerequisiteProgram::create([
            ...$data,
            'tenant_id' => $tenant->id,
            'hazard_analysis_id' => $hazardAnalysis->id,
            'status' => $data['status'] ?? 'Active',
        ]);

        $hazardAnalysis->update(['control_type' => 'OPRP']);
        $this->audit($request, $tenant, 'fsms.oprp.created', OperationalPrerequisiteProgram::class, $oprp->id, [], $oprp->toArray());

        return response()->json(['data' => $oprp->load('responsibleUser:id,name,email')], 201);
    }

    public function storePrp(Request $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'owner_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'verification_frequency' => ['required', 'string', 'max:255'],
            'status' => ['sometimes', 'string', 'max:255'],
        ]);

        $prp = PrerequisiteProgram::create([
            ...$data,
            'tenant_id' => $tenant->id,
            'status' => $data['status'] ?? 'Active',
        ]);

        $this->audit($request, $tenant, 'fsms.prp.created', PrerequisiteProgram::class, $prp->id, [], $prp->toArray());

        return response()->json(['data' => $prp->load('owner:id,name,email')], 201);
    }

    public function storeMonitoringRecord(Request $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validate([
            'monitorable_type' => ['required', 'in:ccp,oprp'],
            'monitorable_id' => ['required', 'integer'],
            'recorded_by_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'measured_value' => ['nullable', 'numeric'],
            'unit' => ['nullable', 'string', 'max:50'],
            'result' => ['required', 'string', 'max:255'],
            'is_deviation' => ['required', 'boolean'],
            'observed_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $monitorable = $this->resolveMonitorable($tenant, $data['monitorable_type'], (int) $data['monitorable_id']);

        $record = DB::transaction(function () use ($data, $monitorable, $request, $tenant): MonitoringRecord {
            $correctiveAction = null;

            if ($data['is_deviation']) {
                $correctiveAction = CorrectiveAction::create([
                    'tenant_id' => $tenant->id,
                    'title' => 'FSMS monitoring deviation: '.$monitorable->name,
                    'description' => $data['notes'] ?? 'Deviation recorded from ISO 22000 monitoring.',
                    'assigned_to_id' => $data['recorded_by_id'] ?? $request->user()->id,
                    'verified_by_id' => $request->user()->id,
                    'due_date' => now()->addDays(3)->toDateString(),
                    'status' => 'Open',
                ]);
            }

            $record = MonitoringRecord::create([
                ...$data,
                'tenant_id' => $tenant->id,
                'monitorable_type' => $monitorable::class,
                'monitorable_id' => $monitorable->id,
                'corrective_action_id' => $correctiveAction?->id,
            ]);

            $this->audit($request, $tenant, 'fsms.monitoring_record.created', MonitoringRecord::class, $record->id, [], $record->toArray());

            if ($correctiveAction) {
                $this->audit($request, $tenant, 'fsms.deviation_capa.created', CorrectiveAction::class, $correctiveAction->id, [], $correctiveAction->toArray());
            }

            return $record;
        });

        return response()->json([
            'data' => $record->load(['monitorable', 'recorder:id,name,email', 'correctiveAction:id,title,status']),
        ], 201);
    }

    private function resolveMonitorable(Tenant $tenant, string $type, int $id): Model
    {
        $model = match ($type) {
            'ccp' => CriticalControlPoint::query()->where('tenant_id', $tenant->id)->find($id),
            'oprp' => OperationalPrerequisiteProgram::query()->where('tenant_id', $tenant->id)->find($id),
        };

        abort_unless($model, 404, 'Monitorable control not found.');

        return $model;
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
