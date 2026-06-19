<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrainingProgramResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'code' => $this->code,
            'title' => $this->title,
            'iso_clause' => $this->iso_clause,
            'delivery_method' => $this->delivery_method,
            'owner_id' => $this->owner_id,
            'owner' => new UserSummaryResource($this->whenLoaded('owner')),
            'refresher_interval_days' => $this->refresher_interval_days,
            'status' => $this->status,
            'description' => $this->description,
            'competency_requirements' => CompetencyRequirementResource::collection($this->whenLoaded('competencyRequirements')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
