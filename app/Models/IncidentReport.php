<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class IncidentReport extends Model
{
    protected $fillable = [
        'tenant_id',
        'reference',
        'title',
        'incident_type',
        'severity',
        'status',
        'reported_by_id',
        'owner_id',
        'source_control_type',
        'source_control_id',
        'detected_at',
        'description',
        'immediate_containment',
        'corrective_action_id',
    ];

    protected function casts(): array
    {
        return ['detected_at' => 'datetime'];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function sourceControl(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'source_control_type', 'source_control_id');
    }

    public function correctiveAction(): BelongsTo
    {
        return $this->belongsTo(CorrectiveAction::class);
    }

    public function actions(): HasMany
    {
        return $this->hasMany(IncidentAction::class);
    }
}
