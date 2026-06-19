<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditFindingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'audit_id' => $this->audit_id,
            'audit' => $this->whenLoaded('audit'),
            'non_conformance_id' => $this->non_conformance_id,
            'non_conformance' => $this->whenLoaded('nonConformance'),
            'reference' => $this->reference,
            'iso_clause' => $this->iso_clause,
            'finding_type' => $this->finding_type,
            'severity' => $this->severity,
            'description' => $this->description,
            'evidence' => $this->evidence,
            'owner_id' => $this->owner_id,
            'owner' => new UserSummaryResource($this->whenLoaded('owner')),
            'due_date' => $this->due_date?->toDateString(),
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
