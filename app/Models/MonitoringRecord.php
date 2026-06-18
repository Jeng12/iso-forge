<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MonitoringRecord extends Model
{
    protected $fillable = [
        'tenant_id',
        'monitorable_type',
        'monitorable_id',
        'recorded_by_id',
        'measured_value',
        'unit',
        'result',
        'is_deviation',
        'observed_at',
        'notes',
        'corrective_action_id',
    ];

    protected function casts(): array
    {
        return [
            'measured_value' => 'decimal:2',
            'is_deviation' => 'boolean',
            'observed_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function monitorable(): MorphTo
    {
        return $this->morphTo();
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_id');
    }

    public function correctiveAction(): BelongsTo
    {
        return $this->belongsTo(CorrectiveAction::class);
    }
}
