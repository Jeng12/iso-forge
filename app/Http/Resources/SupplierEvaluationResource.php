<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierEvaluationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'supplier_id' => $this->supplier_id,
            'supplier' => $this->whenLoaded('supplier'),
            'evaluated_by_id' => $this->evaluated_by_id,
            'evaluator' => new UserSummaryResource($this->whenLoaded('evaluator')),
            'evaluation_date' => $this->evaluation_date?->toDateString(),
            'score' => $this->score,
            'result' => $this->result,
            'next_review_date' => $this->next_review_date?->toDateString(),
            'evidence_document_id' => $this->evidence_document_id,
            'evidence_document' => new DocumentResource($this->whenLoaded('evidenceDocument')),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
