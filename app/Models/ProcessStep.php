<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcessStep extends Model
{
    protected $fillable = ['tenant_id', 'haccp_plan_id', 'sequence', 'name', 'description'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function haccpPlan(): BelongsTo
    {
        return $this->belongsTo(HaccpPlan::class);
    }

    public function hazardAnalyses(): HasMany
    {
        return $this->hasMany(HazardAnalysis::class);
    }
}
