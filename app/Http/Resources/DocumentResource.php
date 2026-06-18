<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'document_number' => $this->document_number,
            'title' => $this->title,
            'category' => $this->category,
            'owner_id' => $this->owner_id,
            'owner' => new UserSummaryResource($this->whenLoaded('owner')),
            'current_version_id' => $this->current_version_id,
            'current_version' => new DocumentVersionResource($this->whenLoaded('currentVersion')),
            'versions' => DocumentVersionResource::collection($this->whenLoaded('versions')),
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
