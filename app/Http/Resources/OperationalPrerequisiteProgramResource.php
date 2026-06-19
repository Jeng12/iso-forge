<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OperationalPrerequisiteProgramResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'hazard_analysis_id' => $this->hazard_analysis_id,
            'hazard_analysis' => $this->whenLoaded('hazardAnalysis'),
            'name' => $this->name,
            'control_measure' => $this->control_measure,
            'monitoring_frequency' => $this->monitoring_frequency,
            'responsible_user_id' => $this->responsible_user_id,
            'responsible_user' => new UserSummaryResource($this->whenLoaded('responsibleUser')),
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
