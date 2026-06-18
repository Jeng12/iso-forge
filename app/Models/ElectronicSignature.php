<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ElectronicSignature extends Model
{
    protected $fillable = [
        'signable_type',
        'signable_id',
        'user_id',
        'reason',
        'meaning',
        'record_hash',
        'ip_address',
        'signed_at',
    ];

    protected function casts(): array
    {
        return [
            'signed_at' => 'datetime',
        ];
    }

    public static function sign(Model $record, User $user, string $meaning, ?string $reason = null): self
    {
        $recordHash = hash('sha256', json_encode([
            'class' => $record::class,
            'id' => $record->getKey(),
            'attributes' => $record->attributesToArray(),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return self::create([
            'signable_type' => $record::class,
            'signable_id' => $record->getKey(),
            'user_id' => $user->id,
            'reason' => $reason,
            'meaning' => $meaning,
            'record_hash' => $recordHash,
            'signed_at' => now(),
        ]);
    }

    public function signable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
