<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'module',
        'auditable_type',
        'auditable_id',
        'description',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function auditable()
    {
        return $this->morphTo();
    }

    /**
     * Registra un evento de auditoría de forma segura sin interrumpir el flujo operativo.
     */
    public static function record(
        string $action,
        string $module,
        string $description,
        ?Model $auditable = null,
        array $metadata = [],
        ?int $userId = null
    ): ?self {
        try {
            if (!Schema::hasTable('audit_logs')) {
                return null;
            }

            $request = request();

            return self::create([
                'user_id' => $userId ?? Auth::id(),
                'action' => $action,
                'module' => $module,
                'auditable_type' => $auditable ? get_class($auditable) : null,
                'auditable_id' => $auditable?->getKey(),
                'description' => $description,
                'metadata' => !empty($metadata) ? $metadata : null,
                'ip_address' => $request?->ip(),
                'user_agent' => $request ? substr((string) $request->userAgent(), 0, 500) : null,
            ]);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
