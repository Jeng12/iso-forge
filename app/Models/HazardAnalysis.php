<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class HazardAnalysis extends Model
{
    protected $fillable = [
        'tenant_id',
        'process_step_id',
        'hazard_type',
        'hazard_description',
        'likelihood',
        'severity',
        'risk_score',
        'control_measure',
        'control_type',
        'status',
    ];

    protected static function booted(): void
    {
        static::saving(function (HazardAnalysis $hazard): void {
            $hazard->risk_score = $hazard->likelihood * $hazard->severity;
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function processStep(): BelongsTo
    {
        return $this->belongsTo(ProcessStep::class);
    }

    public function ccp(): HasOne
    {
        return $this->hasOne(CriticalControlPoint::class);
    }

    public function oprp(): HasOne
    {
        return $this->hasOne(OperationalPrerequisiteProgram::class);
    }
}
