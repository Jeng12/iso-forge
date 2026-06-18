<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class OperationalPrerequisiteProgram extends Model
{
    protected $fillable = [
        'tenant_id',
        'hazard_analysis_id',
        'name',
        'control_measure',
        'monitoring_frequency',
        'responsible_user_id',
        'status',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function hazardAnalysis(): BelongsTo
    {
        return $this->belongsTo(HazardAnalysis::class);
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function monitoringRecords(): MorphMany
    {
        return $this->morphMany(MonitoringRecord::class, 'monitorable');
    }
}
