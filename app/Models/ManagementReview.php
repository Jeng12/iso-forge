<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManagementReview extends Model
{
    protected $fillable = [
        'tenant_id',
        'title',
        'review_date',
        'chair_id',
        'inputs',
        'decisions',
        'actions',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'review_date' => 'date',
            'inputs' => 'array',
            'decisions' => 'array',
            'actions' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function chair(): BelongsTo
    {
        return $this->belongsTo(User::class, 'chair_id');
    }
}
