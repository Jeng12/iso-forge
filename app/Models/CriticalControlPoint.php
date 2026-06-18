<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CriticalControlPoint extends Model
{
    protected $fillable = [
        'tenant_id',
        'hazard_analysis_id',
        'name',
        'critical_limit',
        'monitoring_frequency',
        'responsible_user_id',
        'corrective_action_procedure',
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
