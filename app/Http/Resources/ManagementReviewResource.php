<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ManagementReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'title' => $this->title,
            'review_date' => $this->review_date?->toDateString(),
            'chair_id' => $this->chair_id,
            'chair' => new UserSummaryResource($this->whenLoaded('chair')),
            'inputs' => $this->inputs ?? [],
            'decisions' => $this->decisions ?? [],
            'actions' => $this->actions ?? [],
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
