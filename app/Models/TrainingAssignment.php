<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingAssignment extends Model
{
    protected $fillable = [
        'tenant_id',
        'training_program_id',
        'user_id',
        'assigned_by_id',
        'required_for_role_id',
        'due_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return ['due_date' => 'date'];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function trainingProgram(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_id');
    }

    public function requiredForRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'required_for_role_id');
    }

    public function records(): HasMany
    {
        return $this->hasMany(TrainingRecord::class);
    }
}
