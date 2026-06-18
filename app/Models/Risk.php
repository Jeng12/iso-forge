<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Risk extends Model
{
    protected $fillable = [
        'tenant_id',
        'title',
        'category',
        'likelihood',
        'severity',
        'risk_score',
        'residual_likelihood',
        'residual_severity',
        'residual_score',
        'owner_id',
        'treatment_plan',
        'status',
    ];

    protected static function booted(): void
    {
        static::saving(function (Risk $risk): void {
            $risk->risk_score = $risk->likelihood * $risk->severity;

            if ($risk->residual_likelihood && $risk->residual_severity) {
                $risk->residual_score = $risk->residual_likelihood * $risk->residual_severity;
            }
        });
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
