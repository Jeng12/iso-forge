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
            'retention_until' => $this->retention_until?->toDateString(),
            'status' => $this->status,
            'superseded_at' => $this->superseded_at?->toISOString(),
            'superseded_by_id' => $this->superseded_by_id,
            'superseded_reviewed_at' => $this->superseded_reviewed_at?->toISOString(),
            'superseded_reviewed_by_id' => $this->superseded_reviewed_by_id,
            'superseded_review_notes' => $this->superseded_review_notes,
            'reviewed_by_id' => $this->reviewed_by_id,
            'approved_by_id' => $this->approved_by_id,
            'review_date' => $this->review_date?->toDateString(),
            'approval_date' => $this->approval_date?->toDateString(),
            'change_summary' => $this->change_summary,
            'pruned_at' => $this->pruned_at?->toISOString(),
            'pruned_by_id' => $this->pruned_by_id,
            'prune_reason' => $this->prune_reason,
            'approvals' => DocumentApprovalResource::collection($this->whenLoaded('approvals')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
