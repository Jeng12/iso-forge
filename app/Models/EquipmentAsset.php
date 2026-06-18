<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EquipmentAsset extends Model
{
    protected $fillable = [
        'tenant_id',
        'asset_tag',
        'name',
        'location',
        'owner_id',
        'calibration_interval_days',
        'critical_to_food_safety',
        'last_calibrated_at',
        'next_calibration_due_at',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'critical_to_food_safety' => 'boolean',
            'last_calibrated_at' => 'date',
            'next_calibration_due_at' => 'date',
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

    public function calibrationRecords(): HasMany
    {
        return $this->hasMany(CalibrationRecord::class);
    }
}
