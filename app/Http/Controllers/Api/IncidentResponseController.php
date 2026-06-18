<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
use Illuminate\Validation\Rule;

class IncidentResponseController extends Controller
{
    public function overview(Tenant $tenant): JsonResponse
    {
        return response()->json([
            'data' => [
                'incident_reports' => IncidentReport::query()
                    ->with([
                        'reporter:id,name,email',
                        'owner:id,name,email',
                        'sourceControl',
                        'correctiveAction:id,title,status',
                        'actions.responsibleUser:id,name,email',
                    ])
                    ->where('tenant_id', $tenant->id)
                    ->latest('detected_at')
                    ->get(),
                'actions' => IncidentAction::query()
                    ->with(['incidentReport:id,reference,title,severity,status', 'responsibleUser:id,name,email'])
                    ->where('tenant_id', $tenant->id)
                    ->orderBy('due_date')
                    ->get(),
                'emergency_plans' => EmergencyResponsePlan::query()
                    ->with([
                        'owner:id,name,email',
                        'relatedDocument:id,document_number,title,status',
                        'drills.facilitator:id,name,email',
                        'drills.correctiveAction:id,title,status',
                    ])
                    ->where('tenant_id', $tenant->id)
                    ->orderBy('next_review_due_at')
                    ->get(),
                'emergency_drills' => EmergencyDrill::query()
                    ->with([
                        'emergencyResponsePlan:id,name,status',
                        'facilitator:id,name,email',
                        'correctiveAction:id,title,status',
                    ])
                    ->where('tenant_id', $tenant->id)
                    ->latest('completed_at')
                    ->limit(50)
                    ->get(),
            ],
        ]);
    }

    public function storeReport(Request $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validate([
            'reference' => [
                'required',
                'string',
                'max:255',
                Rule::unique('incident_reports')->where('tenant_id', $tenant->id),
            ],
            'title' => ['required', 'string', 'max:255'],
            'incident_type' => ['required', 'string', 'max:255'],
            'severity' => ['required', 'in:Minor,Major,Critical'],
            'status' => ['sometimes', 'string', 'max:255'],
            'reported_by_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'owner_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'source_control_type' => ['nullable', 'required_with:source_control_id', 'in:ccp,oprp,prp'],
            'source_control_id' => ['nullable', 'required_with:source_control_type', 'integer'],
            'detected_at' => ['required', 'date'],
            'description' => ['required', 'string'],
            'immediate_containment' => ['nullable', 'string'],
        ]);

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
            'data' => $report->load(['reporter:id,name,email', 'owner:id,name,email', 'sourceControl', 'correctiveAction:id,title,status']),
        ], 201);
    }

    public function storeAction(Request $request, Tenant $tenant, IncidentReport $incidentReport): JsonResponse
    {
        abort_unless((int) $incidentReport->tenant_id === (int) $tenant->id, 404);

        $data = $request->validate([
            'action_type' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'responsible_user_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'due_date' => ['nullable', 'date'],
            'completed_at' => ['nullable', 'date'],
            'status' => ['sometimes', 'string', 'max:255'],
        ]);

        $action = IncidentAction::create([
            ...$data,
            'tenant_id' => $tenant->id,
            'incident_report_id' => $incidentReport->id,
            'status' => $data['status'] ?? 'Open',
        ]);

        $this->audit($request, $tenant, 'incident_response.action.created', IncidentAction::class, $action->id, [], $action->toArray());

        return response()->json([
            'data' => $action->load(['incidentReport:id,reference,title,severity,status', 'responsibleUser:id,name,email']),
        ], 201);
    }

    public function updateAction(Request $request, Tenant $tenant, IncidentAction $incidentAction): JsonResponse
    {
        abort_unless((int) $incidentAction->tenant_id === (int) $tenant->id, 404);

        $data = $request->validate([
            'action_type' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'responsible_user_id' => ['sometimes', 'nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'completed_at' => ['sometimes', 'nullable', 'date'],
            'status' => ['sometimes', 'string', 'max:255'],
        ]);

        $oldValues = $incidentAction->toArray();
        $incidentAction->update($data);

        $this->audit($request, $tenant, 'incident_response.action.updated', IncidentAction::class, $incidentAction->id, $oldValues, $incidentAction->fresh()->toArray());

        return response()->json([
            'data' => $incidentAction->load(['incidentReport:id,reference,title,severity,status', 'responsibleUser:id,name,email']),
        ]);
    }

    public function storePlan(Request $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'scenario' => ['required', 'string'],
            'owner_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'related_document_id' => ['nullable', Rule::exists('documents', 'id')->where('tenant_id', $tenant->id)],
            'review_frequency_days' => ['sometimes', 'integer', 'min:1', 'max:3660'],
            'last_reviewed_at' => ['nullable', 'date'],
            'next_review_due_at' => ['nullable', 'date'],
            'response_steps' => ['nullable', 'array'],
            'response_steps.*' => ['string', 'max:255'],
            'status' => ['sometimes', 'string', 'max:255'],
        ]);

        $plan = EmergencyResponsePlan::create([
            ...$data,
            'tenant_id' => $tenant->id,
            'review_frequency_days' => $data['review_frequency_days'] ?? 365,
            'status' => $data['status'] ?? 'Active',
        ]);

        $this->audit($request, $tenant, 'incident_response.plan.created', EmergencyResponsePlan::class, $plan->id, [], $plan->toArray());

        return response()->json([
            'data' => $plan->load(['owner:id,name,email', 'relatedDocument:id,document_number,title,status']),
        ], 201);
    }

    public function storeDrill(Request $request, Tenant $tenant, EmergencyResponsePlan $emergencyResponsePlan): JsonResponse
    {
        abort_unless((int) $emergencyResponsePlan->tenant_id === (int) $tenant->id, 404);

        $data = $request->validate([
            'facilitator_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'scheduled_at' => ['nullable', 'date'],
            'completed_at' => ['required', 'date'],
            'result' => ['required', 'in:Effective,Needs Improvement,Failed'],
            'participants_count' => ['sometimes', 'integer', 'min:0', 'max:10000'],
            'effectiveness_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'scenario_notes' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

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
            'data' => $drill->load(['emergencyResponsePlan:id,name,status,next_review_due_at', 'facilitator:id,name,email', 'correctiveAction:id,title,status']),
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
