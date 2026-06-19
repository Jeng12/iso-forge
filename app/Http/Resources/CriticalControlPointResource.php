<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CriticalControlPointResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'hazard_analysis_id' => $this->hazard_analysis_id,
            'hazard_analysis' => $this->whenLoaded('hazardAnalysis'),
            'name' => $this->name,
            'critical_limit' => $this->critical_limit,
            'monitoring_frequency' => $this->monitoring_frequency,
            'responsible_user_id' => $this->responsible_user_id,
            'responsible_user' => new UserSummaryResource($this->whenLoaded('responsibleUser')),
            'corrective_action_procedure' => $this->corrective_action_procedure,
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
