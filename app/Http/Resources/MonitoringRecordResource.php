<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonitoringRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'monitorable_type' => $this->monitorable_type,
            'monitorable_id' => $this->monitorable_id,
            'monitorable' => $this->whenLoaded('monitorable'),
            'recorded_by_id' => $this->recorded_by_id,
            'recorder' => new UserSummaryResource($this->whenLoaded('recorder')),
            'measured_value' => $this->measured_value,
            'unit' => $this->unit,
            'result' => $this->result,
            'is_deviation' => $this->is_deviation,
            'observed_at' => $this->observed_at?->toISOString(),
            'notes' => $this->notes,
            'corrective_action_id' => $this->corrective_action_id,
            'corrective_action' => $this->whenLoaded('correctiveAction'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
