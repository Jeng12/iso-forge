<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\AwarenessAcknowledgement;
use App\Models\CompetencyRequirement;
use App\Models\CorrectiveAction;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\TrainingAssignment;
use App\Models\TrainingProgram;
use App\Models\TrainingRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TrainingController extends Controller
{
    public function overview(Tenant $tenant): JsonResponse
    {
        return response()->json([
            'data' => [
                'programs' => TrainingProgram::query()
                    ->with(['owner:id,name,email', 'competencyRequirements.role:id,name,slug'])
                    ->where('tenant_id', $tenant->id)
                    ->orderBy('code')
                    ->get(),
                'requirements' => CompetencyRequirement::query()
                    ->with(['role:id,name,slug', 'trainingProgram:id,code,title,status'])
                    ->where('tenant_id', $tenant->id)
                    ->orderBy('competency_area')
                    ->get(),
                'assignments' => TrainingAssignment::query()
                    ->with(['trainingProgram:id,code,title', 'user:id,name,email,job_title', 'assigner:id,name,email', 'requiredForRole:id,name,slug'])
                    ->where('tenant_id', $tenant->id)
                    ->orderBy('due_date')
                    ->get(),
                'records' => TrainingRecord::query()
                    ->with(['trainingProgram:id,code,title', 'user:id,name,email', 'trainer:id,name,email', 'evidenceDocument:id,document_number,title,status', 'correctiveAction:id,title,status'])
                    ->where('tenant_id', $tenant->id)
                    ->latest('completed_at')
                    ->limit(50)
                    ->get(),
                'awareness_acknowledgements' => AwarenessAcknowledgement::query()
                    ->with(['document:id,document_number,title,status', 'user:id,name,email', 'acknowledger:id,name,email'])
                    ->where('tenant_id', $tenant->id)
                    ->latest('acknowledged_at')
                    ->limit(50)
                    ->get(),
                'roles' => Role::query()
                    ->where('tenant_id', $tenant->id)
                    ->orderBy('name')
                    ->get(['id', 'tenant_id', 'name', 'slug']),
            ],
        ]);
    }

    public function storeProgram(Request $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validate([
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('training_programs')->where('tenant_id', $tenant->id),
            ],
            'title' => ['required', 'string', 'max:255'],
            'iso_clause' => ['nullable', 'string', 'max:255'],
            'delivery_method' => ['sometimes', 'string', 'max:255'],
            'owner_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'refresher_interval_days' => ['nullable', 'integer', 'min:1', 'max:3660'],
            'status' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $program = TrainingProgram::create([
            ...$data,
            'tenant_id' => $tenant->id,
            'delivery_method' => $data['delivery_method'] ?? 'Classroom',
            'status' => $data['status'] ?? 'Active',
        ]);

        $this->audit($request, $tenant, 'training.program.created', TrainingProgram::class, $program->id, [], $program->toArray());

        return response()->json(['data' => $program->load('owner:id,name,email')], 201);
    }

    public function storeRequirement(Request $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validate([
            'role_id' => ['required', Rule::exists('roles', 'id')->where('tenant_id', $tenant->id)],
            'training_program_id' => ['required', Rule::exists('training_programs', 'id')->where('tenant_id', $tenant->id)],
            'competency_area' => ['required', 'string', 'max:255'],
            'required_level' => ['sometimes', 'string', 'max:255'],
            'assessment_method' => ['sometimes', 'string', 'max:255'],
            'due_within_days' => ['sometimes', 'integer', 'min:1', 'max:3660'],
            'is_mandatory' => ['sometimes', 'boolean'],
        ]);

        $requirement = CompetencyRequirement::create([
            ...$data,
            'tenant_id' => $tenant->id,
            'required_level' => $data['required_level'] ?? 'Qualified',
            'assessment_method' => $data['assessment_method'] ?? 'Supervisor verification',
            'due_within_days' => $data['due_within_days'] ?? 30,
            'is_mandatory' => $data['is_mandatory'] ?? true,
        ]);

        $this->audit($request, $tenant, 'training.requirement.created', CompetencyRequirement::class, $requirement->id, [], $requirement->toArray());

        return response()->json(['data' => $requirement->load(['role:id,name,slug', 'trainingProgram:id,code,title'])], 201);
    }

    public function storeAssignment(Request $request, Tenant $tenant, TrainingProgram $trainingProgram): JsonResponse
    {
        abort_unless((int) $trainingProgram->tenant_id === (int) $tenant->id, 404);

        $data = $request->validate([
            'user_id' => ['required', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'assigned_by_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'required_for_role_id' => ['nullable', Rule::exists('roles', 'id')->where('tenant_id', $tenant->id)],
            'due_date' => ['required', 'date'],
            'status' => ['sometimes', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $assignment = TrainingAssignment::create([
            ...$data,
            'tenant_id' => $tenant->id,
            'training_program_id' => $trainingProgram->id,
            'assigned_by_id' => $data['assigned_by_id'] ?? $request->user()->id,
            'status' => $data['status'] ?? 'Assigned',
        ]);

        $this->audit($request, $tenant, 'training.assignment.created', TrainingAssignment::class, $assignment->id, [], $assignment->toArray());

        return response()->json([
            'data' => $assignment->load(['trainingProgram:id,code,title', 'user:id,name,email,job_title', 'assigner:id,name,email', 'requiredForRole:id,name,slug']),
        ], 201);
    }

    public function storeRecord(Request $request, Tenant $tenant, TrainingAssignment $trainingAssignment): JsonResponse
    {
        abort_unless((int) $trainingAssignment->tenant_id === (int) $tenant->id, 404);

        $data = $request->validate([
            'trainer_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'evidence_document_id' => ['nullable', Rule::exists('documents', 'id')->where('tenant_id', $tenant->id)],
            'completed_at' => ['required', 'date'],
            'score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'result' => ['required', 'in:Pass,Fail'],
            'competency_status' => ['required', 'in:Competent,Needs Coaching'],
            'expires_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $record = DB::transaction(function () use ($data, $request, $tenant, $trainingAssignment): TrainingRecord {
            $correctiveAction = null;

            if ($data['result'] === 'Fail' || $data['competency_status'] === 'Needs Coaching') {
                $correctiveAction = CorrectiveAction::create([
                    'tenant_id' => $tenant->id,
                    'title' => 'Competency gap: '.$trainingAssignment->trainingProgram->title,
                    'description' => $data['notes'] ?? 'Training completion requires follow-up coaching and effectiveness verification.',
                    'assigned_to_id' => $trainingAssignment->user_id,
                    'verified_by_id' => $request->user()->id,
                    'due_date' => now()->addDays(14)->toDateString(),
                    'status' => 'Open',
                ]);
            }

            $record = TrainingRecord::create([
                ...$data,
                'tenant_id' => $tenant->id,
                'training_assignment_id' => $trainingAssignment->id,
                'training_program_id' => $trainingAssignment->training_program_id,
                'user_id' => $trainingAssignment->user_id,
                'trainer_id' => $data['trainer_id'] ?? $request->user()->id,
                'corrective_action_id' => $correctiveAction?->id,
            ]);

            $oldValues = $trainingAssignment->toArray();
            $trainingAssignment->update([
                'status' => $correctiveAction ? 'Needs Coaching' : 'Completed',
            ]);

            $this->audit($request, $tenant, 'training.assignment.updated', TrainingAssignment::class, $trainingAssignment->id, $oldValues, $trainingAssignment->fresh()->toArray());
            $this->audit($request, $tenant, 'training.record.created', TrainingRecord::class, $record->id, [], $record->toArray());

            if ($correctiveAction) {
                $this->audit($request, $tenant, 'training.competency_capa.created', CorrectiveAction::class, $correctiveAction->id, [], $correctiveAction->toArray());
            }

            return $record;
        });

        return response()->json([
            'data' => $record->load(['trainingProgram:id,code,title', 'user:id,name,email', 'trainer:id,name,email', 'evidenceDocument:id,document_number,title,status', 'correctiveAction:id,title,status']),
        ], 201);
    }

    public function storeAwarenessAcknowledgement(Request $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validate([
            'document_id' => ['required', Rule::exists('documents', 'id')->where('tenant_id', $tenant->id)],
            'user_id' => ['required', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'acknowledged_by_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'acknowledged_at' => ['nullable', 'date'],
            'status' => ['sometimes', 'string', 'max:255'],
            'statement' => ['nullable', 'string'],
        ]);

        $oldValues = AwarenessAcknowledgement::query()
            ->where('document_id', $data['document_id'])
            ->where('user_id', $data['user_id'])
            ->first()
            ?->toArray() ?? [];

        $acknowledgement = AwarenessAcknowledgement::updateOrCreate(
            [
                'document_id' => $data['document_id'],
                'user_id' => $data['user_id'],
            ],
            [
                ...$data,
                'tenant_id' => $tenant->id,
                'acknowledged_by_id' => $data['acknowledged_by_id'] ?? $request->user()->id,
                'acknowledged_at' => $data['acknowledged_at'] ?? now(),
                'status' => $data['status'] ?? 'Acknowledged',
            ],
        );

        $this->audit(
            $request,
            $tenant,
            $oldValues ? 'training.awareness.updated' : 'training.awareness.created',
            AwarenessAcknowledgement::class,
            $acknowledgement->id,
            $oldValues,
            $acknowledgement->toArray(),
        );

        return response()->json([
            'data' => $acknowledgement->load(['document:id,document_number,title,status', 'user:id,name,email', 'acknowledger:id,name,email']),
        ], $oldValues ? 200 : 201);
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
