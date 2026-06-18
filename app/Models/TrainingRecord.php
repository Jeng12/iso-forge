<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingRecord extends Model
{
    protected $fillable = [
        'tenant_id',
        'training_assignment_id',
        'training_program_id',
        'user_id',
        'trainer_id',
        'evidence_document_id',
        'corrective_action_id',
        'completed_at',
        'score',
        'result',
        'competency_status',
        'expires_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'date',
            'score' => 'decimal:2',
            'expires_at' => 'date',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function trainingAssignment(): BelongsTo
    {
        return $this->belongsTo(TrainingAssignment::class);
    }

    public function trainingProgram(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function evidenceDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'evidence_document_id');
    }

    public function correctiveAction(): BelongsTo
    {
        return $this->belongsTo(CorrectiveAction::class);
    }
}
