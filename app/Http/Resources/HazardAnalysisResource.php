<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HazardAnalysisResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'process_step_id' => $this->process_step_id,
            'process_step' => $this->whenLoaded('processStep'),
            'hazard_type' => $this->hazard_type,
            'hazard_description' => $this->hazard_description,
            'likelihood' => $this->likelihood,
            'severity' => $this->severity,
            'risk_score' => $this->risk_score,
            'control_measure' => $this->control_measure,
            'control_type' => $this->control_type,
            'status' => $this->status,
            'ccp' => new CriticalControlPointResource($this->whenLoaded('ccp')),
            'oprp' => new OperationalPrerequisiteProgramResource($this->whenLoaded('oprp')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
