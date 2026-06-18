<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'event',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'previous_hash',
        'payload_hash',
        'entry_hash',
        'ip_address',
        'user_agent',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public static function appendFor(
        ?int $tenantId,
        ?int $userId,
        string $event,
        string $auditableType,
        int|string|null $auditableId = null,
        array $oldValues = [],
        array $newValues = [],
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): self {
        $occurredAt = now();
        $previousHash = self::query()->latest('id')->value('entry_hash') ?? str_repeat('0', 64);

        $payload = [
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'event' => $event,
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'occurred_at' => $occurredAt->toJSON(),
        ];

        $payloadHash = hash('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $entryHash = hash('sha256', $previousHash.$payloadHash);

        return self::create([
            ...$payload,
            'previous_hash' => $previousHash,
            'payload_hash' => $payloadHash,
            'entry_hash' => $entryHash,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
