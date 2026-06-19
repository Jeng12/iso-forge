<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AwarenessAcknowledgementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'document_id' => $this->document_id,
            'document' => new DocumentResource($this->whenLoaded('document')),
            'user_id' => $this->user_id,
            'user' => new UserSummaryResource($this->whenLoaded('user')),
            'acknowledged_by_id' => $this->acknowledged_by_id,
            'acknowledger' => new UserSummaryResource($this->whenLoaded('acknowledger')),
            'acknowledged_at' => $this->acknowledged_at?->toISOString(),
            'status' => $this->status,
            'statement' => $this->statement,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
