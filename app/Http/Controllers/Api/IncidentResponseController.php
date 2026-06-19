<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmergencyDrillRequest;
use App\Http\Requests\StoreEmergencyResponsePlanRequest;
use App\Http\Requests\StoreIncidentActionRequest;
use App\Http\Requests\StoreIncidentReportRequest;
use App\Http\Requests\UpdateEmergencyResponsePlanRequest;
use App\Http\Requests\UpdateIncidentActionRequest;
use App\Http\Requests\UpdateIncidentReportRequest;
use App\Http\Resources\EmergencyDrillResource;
use App\Http\Resources\EmergencyResponsePlanResource;
use App\Http\Resources\IncidentActionResource;
use App\Http\Resources\IncidentReportResource;
use App\Models\AuditLog;
use App\Models\CorrectiveAction;
use App\Models\CriticalControlPoint;
use App\Models\EmergencyDrill;
use App\Models\EmergencyResponsePlan;
use App\Models\IncidentAction;
use App\Models\IncidentReport;
use App\Models\OperationalPrerequisiteProgram;
use App\Models\PrerequisiteProgram;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class IncidentResponseController extends Controller
{
    public function overview(Tenant $tenant): JsonResponse
    {
        return response()->json([
            'data' => [
                'incident_reports' => IncidentReportResource::collection(
                    IncidentReport::query()
                        ->with([
                            'reporter:id,name,email,job_title',
                            'owner:id,name,email,job_title',
                            'sourceControl',
                            'correctiveAction:id,title,status',
                            'actions.responsibleUser:id,name,email,job_title',
                        ])
                        ->where('tenant_id', $tenant->id)
                        ->latest('detected_at')
                        ->get()
                ),
                'actions' => IncidentActionResource::collection(
                    IncidentAction::query()
                        ->with(['incidentReport:id,reference,title,severity,status', 'responsibleUser:id,name,email,job_title'])
                        ->where('tenant_id', $tenant->id)
                        ->orderBy('due_date')
                        ->get()
                ),
                'emergency_plans' => EmergencyResponsePlanResource::collection(
                    EmergencyResponsePlan::query()
                        ->with([
                            'owner:id,name,email,job_title',
                            'relatedDocument:id,tenant_id,document_number,title,category,owner_id,current_version_id,status,created_at,updated_at',
                            'drills.facilitator:id,name,email,job_title',
                            'drills.correctiveAction:id,title,status',
                        ])
                        ->where('tenant_id', $tenant->id)
                        ->orderBy('next_review_due_at')
                        ->get()
                ),
                'emergency_drills' => EmergencyDrillResource::collection(
                    EmergencyDrill::query()
                        ->with([
                            'emergencyResponsePlan:id,name,status',
                            'facilitator:id,name,email,job_title',
                            'correctiveAction:id,title,status',
                        ])
                        ->where('tenant_id', $tenant->id)
                        ->latest('completed_at')
                        ->limit(50)
                        ->get()
                ),
            ],
        ]);
    }

    public function storeReport(StoreIncidentReportRequest $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validated();

        $sourceControl = $this->resolveSourceControl(
            $tenant,
            $data['source_control_type'] ?? null,
            isset($data['source_control_id']) ? (int) $data['source_control_id'] : null,
        );

        $report = DB::transaction(function () use ($data, $request, $sourceControl, $tenant): IncidentReport {
            $correctiveAction = null;

            if (in_array($data['severity'], ['Major', 'Critical'], true)) {
                $correctiveAction = CorrectiveAction::create([
                    'tenant_id' => $tenant->id,
                    'title' => 'Incident response: '.$data['reference'],
                    'description' => $data['description'],
                    'assigned_to_id' => $data['owner_id'] ?? $data['reported_by_id'] ?? $request->user()->id,
                    'verified_by_id' => $request->user()->id,
                    'due_date' => now()->addDays($data['severity'] === 'Critical' ? 2 : 7)->toDateString(),
                    'status' => 'Open',
                ]);
            }

            $report = IncidentReport::create([
                ...$data,
                'tenant_id' => $tenant->id,
                'reported_by_id' => $data['reported_by_id'] ?? $request->user()->id,
                'status' => $data['status'] ?? 'Open',
                'source_control_type' => $sourceControl ? $sourceControl::class : null,
                'source_control_id' => $sourceControl?->id,
                'corrective_action_id' => $correctiveAction?->id,
            ]);

            $this->audit($request, $tenant, 'incident_response.report.created', IncidentReport::class, $report->id, [], $report->toArray());

            if ($correctiveAction) {
                $this->audit($request, $tenant, 'incident_response.incident_capa.created', CorrectiveAction::class, $correctiveAction->id, [], $correctiveAction->toArray());
            }

            return $report;
        });

        return response()->json([
            'data' => new IncidentReportResource($report->load(['reporter:id,name,email,job_title', 'owner:id,name,email,job_title', 'sourceControl', 'correctiveAction:id,title,status'])),
        ], 201);
    }

    public function updateReport(UpdateIncidentReportRequest $request, Tenant $tenant, IncidentReport $incidentReport): JsonResponse
    {
        abort_unless((int) $incidentReport->tenant_id === (int) $tenant->id, 404);

        $data = $request->validated();
        $sourceControl = array_key_exists('source_control_type', $data) || array_key_exists('source_control_id', $data)
            ? $this->resolveSourceControl(
                $tenant,
                $data['source_control_type'] ?? null,
                isset($data['source_control_id']) ? (int) $data['source_control_id'] : null,
            )
            : null;

        if (array_key_exists('source_control_type', $data) || array_key_exists('source_control_id', $data)) {
            $data['source_control_type'] = $sourceControl ? $sourceControl::class : null;
            $data['source_control_id'] = $sourceControl?->id;
        }

        $oldValues = $incidentReport->toArray();
        $incidentReport->update($data);

        $this->audit($request, $tenant, 'incident_response.report.updated', IncidentReport::class, $incidentReport->id, $oldValues, $incidentReport->fresh()->toArray());

        return response()->json(['data' => new IncidentReportResource($incidentReport->fresh(['reporter:id,name,email,job_title', 'owner:id,name,email,job_title', 'sourceControl', 'correctiveAction:id,title,status']))]);
    }

    public function storeAction(StoreIncidentActionRequest $request, Tenant $tenant, IncidentReport $incidentReport): JsonResponse
    {
        abort_unless((int) $incidentReport->tenant_id === (int) $tenant->id, 404);

        $data = $request->validated();

        $action = IncidentAction::create([
            ...$data,
            'tenant_id' => $tenant->id,
            'incident_report_id' => $incidentReport->id,
            'status' => $data['status'] ?? 'Open',
        ]);

        $this->audit($request, $tenant, 'incident_response.action.created', IncidentAction::class, $action->id, [], $action->toArray());

        return response()->json([
            'data' => new IncidentActionResource($action->load(['incidentReport:id,reference,title,severity,status', 'responsibleUser:id,name,email,job_title'])),
        ], 201);
    }

    public function updateAction(UpdateIncidentActionRequest $request, Tenant $tenant, IncidentAction $incidentAction): JsonResponse
    {
        abort_unless((int) $incidentAction->tenant_id === (int) $tenant->id, 404);

        $data = $request->validated();

        $oldValues = $incidentAction->toArray();
        $incidentAction->update($data);

        $this->audit($request, $tenant, 'incident_response.action.updated', IncidentAction::class, $incidentAction->id, $oldValues, $incidentAction->fresh()->toArray());

        return response()->json([
            'data' => new IncidentActionResource($incidentAction->load(['incidentReport:id,reference,title,severity,status', 'responsibleUser:id,name,email,job_title'])),
        ]);
    }

    public function storePlan(StoreEmergencyResponsePlanRequest $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validated();

        $plan = EmergencyResponsePlan::create([
            ...$data,
            'tenant_id' => $tenant->id,
            'review_frequency_days' => $data['review_frequency_days'] ?? 365,
            'status' => $data['status'] ?? 'Active',
        ]);

        $this->audit($request, $tenant, 'incident_response.plan.created', EmergencyResponsePlan::class, $plan->id, [], $plan->toArray());

        return response()->json([
            'data' => new EmergencyResponsePlanResource($plan->load(['owner:id,name,email,job_title', 'relatedDocument:id,tenant_id,document_number,title,category,owner_id,current_version_id,status,created_at,updated_at'])),
        ], 201);
    }

    public function updatePlan(UpdateEmergencyResponsePlanRequest $request, Tenant $tenant, EmergencyResponsePlan $emergencyResponsePlan): JsonResponse
    {
        abort_unless((int) $emergencyResponsePlan->tenant_id === (int) $tenant->id, 404);

        $data = $request->validated();
        $oldValues = $emergencyResponsePlan->toArray();
        $emergencyResponsePlan->update($data);

        $this->audit($request, $tenant, 'incident_response.plan.updated', EmergencyResponsePlan::class, $emergencyResponsePlan->id, $oldValues, $emergencyResponsePlan->fresh()->toArray());

        return response()->json(['data' => new EmergencyResponsePlanResource($emergencyResponsePlan->fresh(['owner:id,name,email,job_title', 'relatedDocument:id,tenant_id,document_number,title,category,owner_id,current_version_id,status,created_at,updated_at']))]);
    }

    public function storeDrill(StoreEmergencyDrillRequest $request, Tenant $tenant, EmergencyResponsePlan $emergencyResponsePlan): JsonResponse
    {
        abort_unless((int) $emergencyResponsePlan->tenant_id === (int) $tenant->id, 404);

        $data = $request->validated();

        $drill = DB::transaction(function () use ($data, $emergencyResponsePlan, $request, $tenant): EmergencyDrill {
            $correctiveAction = null;

            if (in_array($data['result'], ['Needs Improvement', 'Failed'], true)) {
                $correctiveAction = CorrectiveAction::create([
                    'tenant_id' => $tenant->id,
                    'title' => 'Emergency drill follow-up: '.$emergencyResponsePlan->name,
                    'description' => $data['notes'] ?? 'Emergency response drill requires corrective action and effectiveness verification.',
                    'assigned_to_id' => $emergencyResponsePlan->owner_id ?? $data['facilitator_id'] ?? $request->user()->id,
                    'verified_by_id' => $request->user()->id,
                    'due_date' => now()->addDays(10)->toDateString(),
                    'status' => 'Open',
                ]);
            }

            $drill = EmergencyDrill::create([
                ...$data,
                'tenant_id' => $tenant->id,
                'emergency_response_plan_id' => $emergencyResponsePlan->id,
                'facilitator_id' => $data['facilitator_id'] ?? $request->user()->id,
                'participants_count' => $data['participants_count'] ?? 0,
                'corrective_action_id' => $correctiveAction?->id,
            ]);

            $oldValues = $emergencyResponsePlan->toArray();
            $completedAt = Carbon::parse($data['completed_at']);
            $emergencyResponsePlan->update([
                'last_reviewed_at' => $completedAt->toDateString(),
                'next_review_due_at' => $completedAt->copy()->addDays((int) $emergencyResponsePlan->review_frequency_days)->toDateString(),
            ]);

            $this->audit($request, $tenant, 'incident_response.plan.updated', EmergencyResponsePlan::class, $emergencyResponsePlan->id, $oldValues, $emergencyResponsePlan->fresh()->toArray());
            $this->audit($request, $tenant, 'incident_response.drill.created', EmergencyDrill::class, $drill->id, [], $drill->toArray());

            if ($correctiveAction) {
                $this->audit($request, $tenant, 'incident_response.drill_capa.created', CorrectiveAction::class, $correctiveAction->id, [], $correctiveAction->toArray());
            }

            return $drill;
        });

        return response()->json([
            'data' => new EmergencyDrillResource($drill->load(['emergencyResponsePlan:id,name,status,next_review_due_at', 'facilitator:id,name,email,job_title', 'correctiveAction:id,title,status'])),
        ], 201);
    }

    private function resolveSourceControl(Tenant $tenant, ?string $type, ?int $id): ?Model
    {
        if (! $type && ! $id) {
            return null;
        }

        $model = match ($type) {
            'ccp' => CriticalControlPoint::query()->where('tenant_id', $tenant->id)->find($id),
            'oprp' => OperationalPrerequisiteProgram::query()->where('tenant_id', $tenant->id)->find($id),
            'prp' => PrerequisiteProgram::query()->where('tenant_id', $tenant->id)->find($id),
        };

        abort_unless($model, 404, 'Source food-safety control not found.');

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
