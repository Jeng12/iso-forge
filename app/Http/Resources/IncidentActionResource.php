<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IncidentActionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'incident_report_id' => $this->incident_report_id,
            'incident_report' => $this->whenLoaded('incidentReport'),
            'action_type' => $this->action_type,
            'description' => $this->description,
            'responsible_user_id' => $this->responsible_user_id,
            'responsible_user' => new UserSummaryResource($this->whenLoaded('responsibleUser')),
            'due_date' => $this->due_date?->toDateString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
