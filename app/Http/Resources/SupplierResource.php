<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'supplier_code' => $this->supplier_code,
            'category' => $this->category,
            'contact_email' => $this->contact_email,
            'approval_status' => $this->approval_status,
            'risk_level' => $this->risk_level,
            'approved_until' => $this->approved_until?->toDateString(),
            'owner_id' => $this->owner_id,
            'owner' => new UserSummaryResource($this->whenLoaded('owner')),
            'risk_id' => $this->risk_id,
            'risk' => $this->whenLoaded('risk'),
            'notes' => $this->notes,
            'evaluations' => SupplierEvaluationResource::collection($this->whenLoaded('evaluations')),
            'certificates' => SupplierCertificateResource::collection($this->whenLoaded('certificates')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
