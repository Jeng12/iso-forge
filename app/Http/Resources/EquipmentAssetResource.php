<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EquipmentAssetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'asset_tag' => $this->asset_tag,
            'name' => $this->name,
            'location' => $this->location,
            'owner_id' => $this->owner_id,
            'owner' => new UserSummaryResource($this->whenLoaded('owner')),
            'calibration_interval_days' => $this->calibration_interval_days,
            'critical_to_food_safety' => $this->critical_to_food_safety,
            'last_calibrated_at' => $this->last_calibrated_at?->toDateString(),
            'next_calibration_due_at' => $this->next_calibration_due_at?->toDateString(),
            'status' => $this->status,
            'notes' => $this->notes,
            'calibration_records' => CalibrationRecordResource::collection($this->whenLoaded('calibrationRecords')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
