<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrainingRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'training_assignment_id' => $this->training_assignment_id,
            'training_program_id' => $this->training_program_id,
            'training_program' => new TrainingProgramResource($this->whenLoaded('trainingProgram')),
            'user_id' => $this->user_id,
            'user' => new UserSummaryResource($this->whenLoaded('user')),
            'trainer_id' => $this->trainer_id,
            'trainer' => new UserSummaryResource($this->whenLoaded('trainer')),
            'evidence_document_id' => $this->evidence_document_id,
            'evidence_document' => new DocumentResource($this->whenLoaded('evidenceDocument')),
            'corrective_action_id' => $this->corrective_action_id,
            'corrective_action' => $this->whenLoaded('correctiveAction'),
            'completed_at' => $this->completed_at?->toDateString(),
            'score' => $this->score,
            'result' => $this->result,
            'competency_status' => $this->competency_status,
            'expires_at' => $this->expires_at?->toDateString(),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
