<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'supplier_code',
        'category',
        'contact_email',
        'approval_status',
        'risk_level',
        'approved_until',
        'owner_id',
        'risk_id',
        'notes',
    ];

    protected function casts(): array
    {
        return ['approved_until' => 'date'];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function risk(): BelongsTo
    {
        return $this->belongsTo(Risk::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(SupplierEvaluation::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(SupplierCertificate::class);
    }
}
