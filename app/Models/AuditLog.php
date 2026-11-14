<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $guarded = ['id'];

    public static function record(
        string $action,
        string $module,
        string $description,
        ?int $referenceId = null,
        ?string $referenceNumber = null,
        ?array $payload = null
    ): self {
        $user = auth()->user();

        return static::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name ?? 'System',
            'action' => $action,
            'module' => $module,
            'reference_id' => $referenceId,
            'reference_number' => $referenceNumber,
            'description' => $description,
            'payload' => $payload ? json_encode($payload) : null,
            'ip_address' => request()?->ip(),
        ]);
    }
}
