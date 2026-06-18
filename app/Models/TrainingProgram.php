<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingProgram extends Model
{
    protected $fillable = [
        'tenant_id',
        'code',
        'title',
        'iso_clause',
        'delivery_method',
        'owner_id',
        'refresher_interval_days',
        'status',
        'description',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function competencyRequirements(): HasMany
    {
        return $this->hasMany(CompetencyRequirement::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TrainingAssignment::class);
    }

    public function records(): HasMany
    {
        return $this->hasMany(TrainingRecord::class);
    }
}
