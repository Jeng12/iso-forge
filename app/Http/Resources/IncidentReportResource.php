<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IncidentReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'reference' => $this->reference,
            'title' => $this->title,
            'incident_type' => $this->incident_type,
            'severity' => $this->severity,
            'status' => $this->status,
            'reported_by_id' => $this->reported_by_id,
            'reporter' => new UserSummaryResource($this->whenLoaded('reporter')),
            'owner_id' => $this->owner_id,
            'owner' => new UserSummaryResource($this->whenLoaded('owner')),
            'source_control_type' => $this->source_control_type,
            'source_control_id' => $this->source_control_id,
            'source_control' => $this->whenLoaded('sourceControl'),
            'detected_at' => $this->detected_at?->toISOString(),
            'description' => $this->description,
            'immediate_containment' => $this->immediate_containment,
            'corrective_action_id' => $this->corrective_action_id,
            'corrective_action' => $this->whenLoaded('correctiveAction'),
            'actions' => IncidentActionResource::collection($this->whenLoaded('actions')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
