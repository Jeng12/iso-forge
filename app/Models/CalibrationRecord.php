<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalibrationRecord extends Model
{
    protected $fillable = [
        'tenant_id',
        'equipment_asset_id',
        'performed_by_id',
        'evidence_document_id',
        'corrective_action_id',
        'performed_at',
        'due_at',
        'result',
        'certificate_number',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'performed_at' => 'date',
            'due_at' => 'date',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function equipmentAsset(): BelongsTo
    {
        return $this->belongsTo(EquipmentAsset::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_id');
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
