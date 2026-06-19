<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QualityObjectiveResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'title' => $this->title,
            'iso_clause' => $this->iso_clause,
            'baseline_value' => $this->baseline_value,
            'target_value' => $this->target_value,
            'current_value' => $this->current_value,
            'unit' => $this->unit,
            'measurement_method' => $this->measurement_method,
            'owner_id' => $this->owner_id,
            'owner' => new UserSummaryResource($this->whenLoaded('owner')),
            'due_date' => $this->due_date?->toDateString(),
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
