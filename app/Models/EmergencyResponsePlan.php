<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmergencyResponsePlan extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'scenario',
        'owner_id',
        'related_document_id',
        'review_frequency_days',
        'last_reviewed_at',
        'next_review_due_at',
        'response_steps',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'last_reviewed_at' => 'date',
            'next_review_due_at' => 'date',
            'response_steps' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function relatedDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'related_document_id');
    }

    public function drills(): HasMany
    {
        return $this->hasMany(EmergencyDrill::class);
    }
}
