<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NonConformance extends Model
{
    protected $fillable = [
        'tenant_id',
        'reference',
        'source',
        'description',
        'iso_clause',
        'severity',
        'status',
        'detected_at',
        'owner_id',
        'root_cause',
    ];

    protected function casts(): array
    {
        return [
            'detected_at' => 'date',
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

    public function correctiveActions(): HasMany
    {
        return $this->hasMany(CorrectiveAction::class);
    }
}
