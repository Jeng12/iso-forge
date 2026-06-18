<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmergencyDrill extends Model
{
    protected $fillable = [
        'tenant_id',
        'emergency_response_plan_id',
        'facilitator_id',
        'scheduled_at',
        'completed_at',
        'result',
        'participants_count',
        'effectiveness_score',
        'scenario_notes',
        'notes',
        'corrective_action_id',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'date',
            'completed_at' => 'date',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function emergencyResponsePlan(): BelongsTo
    {
        return $this->belongsTo(EmergencyResponsePlan::class);
    }

    public function facilitator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'facilitator_id');
    }

    public function correctiveAction(): BelongsTo
    {
        return $this->belongsTo(CorrectiveAction::class);
    }
}
