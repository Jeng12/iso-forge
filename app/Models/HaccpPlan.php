<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HaccpPlan extends Model
{
    protected $fillable = ['tenant_id', 'name', 'product', 'scope', 'owner_id', 'effective_date', 'status'];

    protected function casts(): array
    {
        return ['effective_date' => 'date'];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function processSteps(): HasMany
    {
        return $this->hasMany(ProcessStep::class);
    }
}
