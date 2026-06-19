<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HaccpPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'product' => $this->product,
            'scope' => $this->scope,
            'owner_id' => $this->owner_id,
            'owner' => new UserSummaryResource($this->whenLoaded('owner')),
            'effective_date' => $this->effective_date?->toDateString(),
            'status' => $this->status,
            'process_steps' => ProcessStepResource::collection($this->whenLoaded('processSteps')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
