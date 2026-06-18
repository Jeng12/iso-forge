<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualityObjective extends Model
{
    protected $fillable = [
        'tenant_id',
        'title',
        'iso_clause',
        'baseline_value',
        'target_value',
        'current_value',
        'unit',
        'measurement_method',
        'owner_id',
        'due_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'baseline_value' => 'decimal:2',
            'target_value' => 'decimal:2',
            'current_value' => 'decimal:2',
            'due_date' => 'date',
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
}
