<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProcessStepResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'haccp_plan_id' => $this->haccp_plan_id,
            'haccp_plan' => $this->whenLoaded('haccpPlan'),
            'sequence' => $this->sequence,
            'name' => $this->name,
            'description' => $this->description,
            'hazard_analyses' => HazardAnalysisResource::collection($this->whenLoaded('hazardAnalyses')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
