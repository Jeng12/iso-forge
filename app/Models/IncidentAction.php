<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentAction extends Model
{
    protected $fillable = [
        'tenant_id',
        'incident_report_id',
        'action_type',
        'description',
        'responsible_user_id',
        'due_date',
        'completed_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function incidentReport(): BelongsTo
    {
        return $this->belongsTo(IncidentReport::class);
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }
}
