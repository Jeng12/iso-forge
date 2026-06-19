<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAwarenessAcknowledgementRequest;
use App\Http\Requests\StoreCompetencyRequirementRequest;
use App\Http\Requests\StoreTrainingAssignmentRequest;
use App\Http\Requests\StoreTrainingProgramRequest;
use App\Http\Requests\StoreTrainingRecordRequest;
use App\Http\Requests\UpdateTrainingAssignmentRequest;
use App\Http\Requests\UpdateTrainingProgramRequest;
use App\Http\Resources\AwarenessAcknowledgementResource;
use App\Http\Resources\CompetencyRequirementResource;
use App\Http\Resources\RoleResource;
use App\Http\Resources\TrainingAssignmentResource;
use App\Http\Resources\TrainingProgramResource;
use App\Http\Resources\TrainingRecordResource;
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

class TrainingController extends Controller
{
    public function overview(Tenant $tenant): JsonResponse
    {
        return response()->json([
            'data' => [
                'programs' => TrainingProgramResource::collection(
                    TrainingProgram::query()
                        ->with(['owner:id,name,email,job_title', 'competencyRequirements.role:id,name,slug'])
                        ->where('tenant_id', $tenant->id)
                        ->orderBy('code')
                        ->get()
                ),
                'requirements' => CompetencyRequirementResource::collection(
                    CompetencyRequirement::query()
                        ->with(['role:id,name,slug', 'trainingProgram:id,tenant_id,code,title,iso_clause,delivery_method,owner_id,refresher_interval_days,status,description,created_at,updated_at'])
                        ->where('tenant_id', $tenant->id)
                        ->orderBy('competency_area')
                        ->get()
                ),
                'assignments' => TrainingAssignmentResource::collection(
                    TrainingAssignment::query()
                        ->with(['trainingProgram:id,tenant_id,code,title,iso_clause,delivery_method,owner_id,refresher_interval_days,status,description,created_at,updated_at', 'user:id,name,email,job_title', 'assigner:id,name,email,job_title', 'requiredForRole:id,name,slug'])
                        ->where('tenant_id', $tenant->id)
                        ->orderBy('due_date')
                        ->get()
                ),
                'records' => TrainingRecordResource::collection(
                    TrainingRecord::query()
                        ->with(['trainingProgram:id,tenant_id,code,title,iso_clause,delivery_method,owner_id,refresher_interval_days,status,description,created_at,updated_at', 'user:id,name,email,job_title', 'trainer:id,name,email,job_title', 'evidenceDocument:id,tenant_id,document_number,title,category,owner_id,current_version_id,status,created_at,updated_at', 'correctiveAction:id,title,status'])
                        ->where('tenant_id', $tenant->id)
                        ->latest('completed_at')
                        ->limit(50)
                        ->get()
                ),
                'awareness_acknowledgements' => AwarenessAcknowledgementResource::collection(
                    AwarenessAcknowledgement::query()
                        ->with(['document:id,tenant_id,document_number,title,category,owner_id,current_version_id,status,created_at,updated_at', 'user:id,name,email,job_title', 'acknowledger:id,name,email,job_title'])
                        ->where('tenant_id', $tenant->id)
                        ->latest('acknowledged_at')
                        ->limit(50)
                        ->get()
                ),
                'roles' => RoleResource::collection(
                    Role::query()
                        ->where('tenant_id', $tenant->id)
                        ->orderBy('name')
                        ->get(['id', 'tenant_id', 'name', 'slug'])
                ),
            ],
        ]);
    }

    public function storeProgram(StoreTrainingProgramRequest $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validated();

        $program = TrainingProgram::create([
            ...$data,
            'tenant_id' => $tenant->id,
            'delivery_method' => $data['delivery_method'] ?? 'Classroom',
            'status' => $data['status'] ?? 'Active',
        ]);

        $this->audit($request, $tenant, 'training.program.created', TrainingProgram::class, $program->id, [], $program->toArray());

        return response()->json(['data' => new TrainingProgramResource($program->load('owner:id,name,email,job_title'))], 201);
    }

    public function updateProgram(UpdateTrainingProgramRequest $request, Tenant $tenant, TrainingProgram $trainingProgram): JsonResponse
    {
        abort_unless((int) $trainingProgram->tenant_id === (int) $tenant->id, 404);

        $data = $request->validated();
        $oldValues = $trainingProgram->toArray();
        $trainingProgram->update($data);

        $this->audit($request, $tenant, 'training.program.updated', TrainingProgram::class, $trainingProgram->id, $oldValues, $trainingProgram->fresh()->toArray());

        return response()->json(['data' => new TrainingProgramResource($trainingProgram->fresh('owner:id,name,email,job_title'))]);
    }

    public function storeRequirement(StoreCompetencyRequirementRequest $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validated();

        $requirement = CompetencyRequirement::create([
            ...$data,
            'tenant_id' => $tenant->id,
            'required_level' => $data['required_level'] ?? 'Qualified',
            'assessment_method' => $data['assessment_method'] ?? 'Supervisor verification',
            'due_within_days' => $data['due_within_days'] ?? 30,
            'is_mandatory' => $data['is_mandatory'] ?? true,
        ]);

        $this->audit($request, $tenant, 'training.requirement.created', CompetencyRequirement::class, $requirement->id, [], $requirement->toArray());

        return response()->json(['data' => new CompetencyRequirementResource($requirement->load(['role:id,name,slug', 'trainingProgram:id,tenant_id,code,title,iso_clause,delivery_method,owner_id,refresher_interval_days,status,description,created_at,updated_at']))], 201);
    }

    public function storeAssignment(StoreTrainingAssignmentRequest $request, Tenant $tenant, TrainingProgram $trainingProgram): JsonResponse
    {
        abort_unless((int) $trainingProgram->tenant_id === (int) $tenant->id, 404);

        $data = $request->validated();

        $assignment = TrainingAssignment::create([
            ...$data,
            'tenant_id' => $tenant->id,
            'training_program_id' => $trainingProgram->id,
            'assigned_by_id' => $data['assigned_by_id'] ?? $request->user()->id,
            'status' => $data['status'] ?? 'Assigned',
        ]);

        $this->audit($request, $tenant, 'training.assignment.created', TrainingAssignment::class, $assignment->id, [], $assignment->toArray());

        return response()->json([
            'data' => new TrainingAssignmentResource($assignment->load(['trainingProgram:id,tenant_id,code,title,iso_clause,delivery_method,owner_id,refresher_interval_days,status,description,created_at,updated_at', 'user:id,name,email,job_title', 'assigner:id,name,email,job_title', 'requiredForRole:id,name,slug'])),
        ], 201);
    }

    public function updateAssignment(UpdateTrainingAssignmentRequest $request, Tenant $tenant, TrainingAssignment $trainingAssignment): JsonResponse
    {
        abort_unless((int) $trainingAssignment->tenant_id === (int) $tenant->id, 404);

        $data = $request->validated();
        $oldValues = $trainingAssignment->toArray();
        $trainingAssignment->update($data);

        $this->audit($request, $tenant, 'training.assignment.updated', TrainingAssignment::class, $trainingAssignment->id, $oldValues, $trainingAssignment->fresh()->toArray());

        return response()->json(['data' => new TrainingAssignmentResource($trainingAssignment->fresh(['trainingProgram:id,tenant_id,code,title,iso_clause,delivery_method,owner_id,refresher_interval_days,status,description,created_at,updated_at', 'user:id,name,email,job_title', 'assigner:id,name,email,job_title', 'requiredForRole:id,name,slug']))]);
    }

    public function storeRecord(StoreTrainingRecordRequest $request, Tenant $tenant, TrainingAssignment $trainingAssignment): JsonResponse
    {
        abort_unless((int) $trainingAssignment->tenant_id === (int) $tenant->id, 404);

        $data = $request->validated();

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
            'data' => new TrainingRecordResource($record->load(['trainingProgram:id,tenant_id,code,title,iso_clause,delivery_method,owner_id,refresher_interval_days,status,description,created_at,updated_at', 'user:id,name,email,job_title', 'trainer:id,name,email,job_title', 'evidenceDocument:id,tenant_id,document_number,title,category,owner_id,current_version_id,status,created_at,updated_at', 'correctiveAction:id,title,status'])),
        ], 201);
    }

    public function storeAwarenessAcknowledgement(StoreAwarenessAcknowledgementRequest $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validated();

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
            'data' => new AwarenessAcknowledgementResource($acknowledgement->load(['document:id,tenant_id,document_number,title,category,owner_id,current_version_id,status,created_at,updated_at', 'user:id,name,email,job_title', 'acknowledger:id,name,email,job_title'])),
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
