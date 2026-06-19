<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CalibrationRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'equipment_asset_id' => $this->equipment_asset_id,
            'equipment_asset' => $this->whenLoaded('equipmentAsset'),
            'performed_by_id' => $this->performed_by_id,
            'performer' => new UserSummaryResource($this->whenLoaded('performer')),
            'evidence_document_id' => $this->evidence_document_id,
            'evidence_document' => new DocumentResource($this->whenLoaded('evidenceDocument')),
            'corrective_action_id' => $this->corrective_action_id,
            'corrective_action' => $this->whenLoaded('correctiveAction'),
            'performed_at' => $this->performed_at?->toDateString(),
            'due_at' => $this->due_at?->toDateString(),
            'result' => $this->result,
            'certificate_number' => $this->certificate_number,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
