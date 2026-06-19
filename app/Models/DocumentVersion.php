<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class DocumentVersion extends Model
{
    protected $fillable = [
        'document_id',
        'version_number',
        'file_path',
        'mime_type',
        'file_size',
        'effective_date',
        'retention_until',
        'status',
        'superseded_at',
        'superseded_by_id',
        'superseded_reviewed_at',
        'superseded_reviewed_by_id',
        'superseded_review_notes',
        'reviewed_by_id',
        'approved_by_id',
        'review_date',
        'approval_date',
        'change_summary',
        'pruned_at',
        'pruned_by_id',
        'prune_reason',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'retention_until' => 'date',
            'superseded_at' => 'datetime',
            'superseded_reviewed_at' => 'datetime',
            'review_date' => 'date',
            'approval_date' => 'date',
            'pruned_at' => 'datetime',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(DocumentApproval::class);
    }

    public function supersededBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'superseded_by_id');
    }

    public function supersededReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'superseded_reviewed_by_id');
    }

    public function prunedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pruned_by_id');
    }

    public function signatures(): MorphMany
    {
        return $this->morphMany(ElectronicSignature::class, 'signable');
    }
}
