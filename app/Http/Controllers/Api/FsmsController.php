<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCriticalControlPointRequest;
use App\Http\Requests\StoreHaccpPlanRequest;
use App\Http\Requests\StoreHazardAnalysisRequest;
use App\Http\Requests\StoreMonitoringRecordRequest;
use App\Http\Requests\StoreOperationalPrerequisiteProgramRequest;
use App\Http\Requests\StorePrerequisiteProgramRequest;
use App\Http\Requests\StoreProcessStepRequest;
use App\Http\Requests\UpdateHaccpPlanRequest;
use App\Http\Resources\CriticalControlPointResource;
use App\Http\Resources\HaccpPlanResource;
use App\Http\Resources\HazardAnalysisResource;
use App\Http\Resources\MonitoringRecordResource;
use App\Http\Resources\OperationalPrerequisiteProgramResource;
use App\Http\Resources\PrerequisiteProgramResource;
use App\Http\Resources\ProcessStepResource;
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

class FsmsController extends Controller
{
    public function overview(Tenant $tenant): JsonResponse
    {
        return response()->json([
            'data' => [
                'haccp_plans' => HaccpPlanResource::collection(
                    HaccpPlan::query()
                        ->with(['owner:id,name,email,job_title', 'processSteps.hazardAnalyses.ccp', 'processSteps.hazardAnalyses.oprp'])
                        ->where('tenant_id', $tenant->id)
                        ->latest()
                        ->get()
                ),
                'hazards' => HazardAnalysisResource::collection(
                    HazardAnalysis::query()
                        ->with('processStep.haccpPlan:id,name,product')
                        ->where('tenant_id', $tenant->id)
                        ->orderByDesc('risk_score')
                        ->get()
                ),
                'ccps' => CriticalControlPointResource::collection(
                    CriticalControlPoint::query()
                        ->with(['hazardAnalysis.processStep.haccpPlan:id,name,product', 'responsibleUser:id,name,email,job_title'])
                        ->where('tenant_id', $tenant->id)
                        ->get()
                ),
                'oprps' => OperationalPrerequisiteProgramResource::collection(
                    OperationalPrerequisiteProgram::query()
                        ->with(['hazardAnalysis.processStep.haccpPlan:id,name,product', 'responsibleUser:id,name,email,job_title'])
                        ->where('tenant_id', $tenant->id)
                        ->get()
                ),
                'prps' => PrerequisiteProgramResource::collection(
                    PrerequisiteProgram::query()
                        ->with('owner:id,name,email,job_title')
                        ->where('tenant_id', $tenant->id)
                        ->get()
                ),
                'monitoring_records' => MonitoringRecordResource::collection(
                    MonitoringRecord::query()
                        ->with(['monitorable', 'recorder:id,name,email,job_title', 'correctiveAction:id,title,status'])
                        ->where('tenant_id', $tenant->id)
                        ->latest('observed_at')
                        ->limit(50)
                        ->get()
                ),
            ],
        ]);
    }

    public function storePlan(StoreHaccpPlanRequest $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validated();

        $plan = HaccpPlan::create([
            ...$data,
            'tenant_id' => $tenant->id,
            'status' => $data['status'] ?? 'Draft',
        ]);

        $this->audit($request, $tenant, 'fsms.haccp_plan.created', HaccpPlan::class, $plan->id, [], $plan->toArray());

        return response()->json(['data' => new HaccpPlanResource($plan->load('owner:id,name,email,job_title'))], 201);
    }

    public function updatePlan(UpdateHaccpPlanRequest $request, Tenant $tenant, HaccpPlan $haccpPlan): JsonResponse
    {
        abort_unless((int) $haccpPlan->tenant_id === (int) $tenant->id, 404);

        $data = $request->validated();
        $oldValues = $haccpPlan->toArray();
        $haccpPlan->update($data);

        $this->audit($request, $tenant, 'fsms.haccp_plan.updated', HaccpPlan::class, $haccpPlan->id, $oldValues, $haccpPlan->fresh()->toArray());

        return response()->json(['data' => new HaccpPlanResource($haccpPlan->fresh(['owner:id,name,email,job_title', 'processSteps.hazardAnalyses.ccp', 'processSteps.hazardAnalyses.oprp']))]);
    }

    public function storeStep(StoreProcessStepRequest $request, Tenant $tenant, HaccpPlan $haccpPlan): JsonResponse
    {
        abort_unless((int) $haccpPlan->tenant_id === (int) $tenant->id, 404);

        $data = $request->validated();

        $step = ProcessStep::create([
            ...$data,
            'tenant_id' => $tenant->id,
            'haccp_plan_id' => $haccpPlan->id,
        ]);

        $this->audit($request, $tenant, 'fsms.process_step.created', ProcessStep::class, $step->id, [], $step->toArray());

        return response()->json(['data' => new ProcessStepResource($step)], 201);
    }

    public function storeHazard(StoreHazardAnalysisRequest $request, Tenant $tenant, ProcessStep $processStep): JsonResponse
    {
        abort_unless((int) $processStep->tenant_id === (int) $tenant->id, 404);

        $data = $request->validated();

        $hazard = HazardAnalysis::create([
            ...$data,
            'tenant_id' => $tenant->id,
            'process_step_id' => $processStep->id,
            'status' => $data['status'] ?? 'Assessed',
        ]);

        $this->audit($request, $tenant, 'fsms.hazard.created', HazardAnalysis::class, $hazard->id, [], $hazard->toArray());

        return response()->json(['data' => new HazardAnalysisResource($hazard->load('processStep.haccpPlan:id,name,product'))], 201);
    }

    public function storeCcp(StoreCriticalControlPointRequest $request, Tenant $tenant, HazardAnalysis $hazardAnalysis): JsonResponse
    {
        abort_unless((int) $hazardAnalysis->tenant_id === (int) $tenant->id, 404);

        $data = $request->validated();

        $ccp = CriticalControlPoint::create([
            ...$data,
            'tenant_id' => $tenant->id,
            'hazard_analysis_id' => $hazardAnalysis->id,
            'status' => $data['status'] ?? 'Active',
        ]);

        $hazardAnalysis->update(['control_type' => 'CCP']);
        $this->audit($request, $tenant, 'fsms.ccp.created', CriticalControlPoint::class, $ccp->id, [], $ccp->toArray());

        return response()->json(['data' => new CriticalControlPointResource($ccp->load('responsibleUser:id,name,email,job_title'))], 201);
    }

    public function storeOprp(StoreOperationalPrerequisiteProgramRequest $request, Tenant $tenant, HazardAnalysis $hazardAnalysis): JsonResponse
    {
        abort_unless((int) $hazardAnalysis->tenant_id === (int) $tenant->id, 404);

        $data = $request->validated();

        $oprp = OperationalPrerequisiteProgram::create([
            ...$data,
            'tenant_id' => $tenant->id,
            'hazard_analysis_id' => $hazardAnalysis->id,
            'status' => $data['status'] ?? 'Active',
        ]);

        $hazardAnalysis->update(['control_type' => 'OPRP']);
        $this->audit($request, $tenant, 'fsms.oprp.created', OperationalPrerequisiteProgram::class, $oprp->id, [], $oprp->toArray());

        return response()->json(['data' => new OperationalPrerequisiteProgramResource($oprp->load('responsibleUser:id,name,email,job_title'))], 201);
    }

    public function storePrp(StorePrerequisiteProgramRequest $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validated();

        $prp = PrerequisiteProgram::create([
            ...$data,
            'tenant_id' => $tenant->id,
            'status' => $data['status'] ?? 'Active',
        ]);

        $this->audit($request, $tenant, 'fsms.prp.created', PrerequisiteProgram::class, $prp->id, [], $prp->toArray());

        return response()->json(['data' => new PrerequisiteProgramResource($prp->load('owner:id,name,email,job_title'))], 201);
    }

    public function storeMonitoringRecord(StoreMonitoringRecordRequest $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validated();

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
            'data' => new MonitoringRecordResource($record->load(['monitorable', 'recorder:id,name,email,job_title', 'correctiveAction:id,title,status'])),
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
