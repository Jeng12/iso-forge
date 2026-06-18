<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class DocumentVersionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'document_id' => $this->document_id,
            'version_number' => $this->version_number,
            'file_path' => $this->file_path,
            'is_stored' => $this->file_path ? Storage::disk('local')->exists($this->file_path) : false,
            'mime_type' => $this->mime_type,
            'file_size' => $this->file_size,
            'effective_date' => $this->effective_date?->toDateString(),
            'status' => $this->status,
            'reviewed_by_id' => $this->reviewed_by_id,
            'approved_by_id' => $this->approved_by_id,
            'review_date' => $this->review_date?->toDateString(),
            'approval_date' => $this->approval_date?->toDateString(),
            'change_summary' => $this->change_summary,
            'approvals' => DocumentApprovalResource::collection($this->whenLoaded('approvals')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
