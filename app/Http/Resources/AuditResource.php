<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'title' => $this->title,
            'audit_type' => $this->audit_type,
            'iso_standard' => $this->iso_standard,
            'scope' => $this->scope,
            'lead_auditor_id' => $this->lead_auditor_id,
            'lead_auditor' => new UserSummaryResource($this->whenLoaded('leadAuditor')),
            'scheduled_date' => $this->scheduled_date?->toDateString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'status' => $this->status,
            'summary' => $this->summary,
            'findings' => AuditFindingResource::collection($this->whenLoaded('findings')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
