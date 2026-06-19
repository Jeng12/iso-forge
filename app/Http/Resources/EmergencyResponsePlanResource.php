<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmergencyResponsePlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'scenario' => $this->scenario,
            'owner_id' => $this->owner_id,
            'owner' => new UserSummaryResource($this->whenLoaded('owner')),
            'related_document_id' => $this->related_document_id,
            'related_document' => new DocumentResource($this->whenLoaded('relatedDocument')),
            'review_frequency_days' => $this->review_frequency_days,
            'last_reviewed_at' => $this->last_reviewed_at?->toDateString(),
            'next_review_due_at' => $this->next_review_due_at?->toDateString(),
            'response_steps' => $this->response_steps ?? [],
            'status' => $this->status,
            'drills' => EmergencyDrillResource::collection($this->whenLoaded('drills')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
