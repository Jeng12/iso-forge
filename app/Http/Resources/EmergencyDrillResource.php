<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmergencyDrillResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'emergency_response_plan_id' => $this->emergency_response_plan_id,
            'emergency_response_plan' => $this->whenLoaded('emergencyResponsePlan'),
            'facilitator_id' => $this->facilitator_id,
            'facilitator' => new UserSummaryResource($this->whenLoaded('facilitator')),
            'scheduled_at' => $this->scheduled_at?->toDateString(),
            'completed_at' => $this->completed_at?->toDateString(),
            'result' => $this->result,
            'participants_count' => $this->participants_count,
            'effectiveness_score' => $this->effectiveness_score,
            'scenario_notes' => $this->scenario_notes,
            'notes' => $this->notes,
            'corrective_action_id' => $this->corrective_action_id,
            'corrective_action' => $this->whenLoaded('correctiveAction'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
