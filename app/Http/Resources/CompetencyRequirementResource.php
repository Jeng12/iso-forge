<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompetencyRequirementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'role_id' => $this->role_id,
            'role' => new RoleResource($this->whenLoaded('role')),
            'training_program_id' => $this->training_program_id,
            'training_program' => new TrainingProgramResource($this->whenLoaded('trainingProgram')),
            'competency_area' => $this->competency_area,
            'required_level' => $this->required_level,
            'assessment_method' => $this->assessment_method,
            'due_within_days' => $this->due_within_days,
            'is_mandatory' => $this->is_mandatory,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
