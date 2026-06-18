<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentApprovalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'document_version_id' => $this->document_version_id,
            'approver_id' => $this->approver_id,
            'approver' => new UserSummaryResource($this->whenLoaded('approver')),
            'status' => $this->status,
            'comments' => $this->comments,
            'approved_at' => $this->approved_at?->toISOString(),
        ];
    }
}
