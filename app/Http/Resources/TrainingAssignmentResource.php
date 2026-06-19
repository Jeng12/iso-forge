<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrainingAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'training_program_id' => $this->training_program_id,
            'training_program' => new TrainingProgramResource($this->whenLoaded('trainingProgram')),
            'user_id' => $this->user_id,
            'user' => new UserSummaryResource($this->whenLoaded('user')),
            'assigned_by_id' => $this->assigned_by_id,
            'assigner' => new UserSummaryResource($this->whenLoaded('assigner')),
            'required_for_role_id' => $this->required_for_role_id,
            'required_for_role' => new RoleResource($this->whenLoaded('requiredForRole')),
            'due_date' => $this->due_date?->toDateString(),
            'status' => $this->status,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
