<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
     * Registra un evento de auditoría de forma segura sin interrumpir el flujo operativo
     * y emite concurrentemente el log al canal de registro del framework (Log Facade).
     */
    public static function record(
        string $action,
        string $module,
        string $description,
        ?Model $auditable = null,
        array $metadata = [],
        ?int $userId = null
    ): ?self {
        $request = request();
        $effectiveUserId = $userId ?? Auth::id();

        // 1. Emisión estructurada al canal de logs del sistema
        try {
            $logContext = [
                'user_id' => $effectiveUserId,
                'action' => $action,
                'module' => $module,
                'auditable_type' => $auditable ? get_class($auditable) : null,
                'auditable_id' => $auditable?->getKey(),
                'ip' => $request?->ip(),
                'metadata' => $metadata,
            ];

            if (
                str_starts_with($action, 'security.') ||
                str_contains($action, 'denied') ||
                str_contains($action, 'unauthorized') ||
                str_contains($action, 'lockout')
            ) {
                Log::warning("[AUDIT-SECURITY] {$module}: {$description}", $logContext);
            } elseif (
                str_starts_with($action, 'error.') ||
                str_starts_with($action, 'system.error') ||
                str_starts_with($action, 'system.exception') ||
                str_contains($action, 'fail')
            ) {
                Log::error("[AUDIT-ERROR] {$module}: {$description}", $logContext);
            } else {
                Log::info("[AUDIT-ACTION] {$module}: {$description}", $logContext);
            }
        } catch (\Throwable) {
            // Prevenir que un fallo en el logger interrumpa la operación
        }

        // 2. Persistencia en la tabla audit_logs
        try {
            if (! Schema::hasTable('audit_logs')) {
                return null;
            }

            return self::create([
                'user_id' => $effectiveUserId,
                'action' => $action,
                'module' => $module,
                'auditable_type' => $auditable ? get_class($auditable) : null,
                'auditable_id' => $auditable?->getKey(),
                'description' => $description,
                'metadata' => ! empty($metadata) ? $metadata : null,
                'ip_address' => $request?->ip(),
                'user_agent' => $request ? substr((string) $request->userAgent(), 0, 500) : null,
            ]);
        } catch (\Throwable $e) {
            Log::error("[AUDIT-DB-FAIL] Fallo al guardar en audit_logs: {$e->getMessage()}", [
                'action' => $action,
                'module' => $module,
                'exception' => $e,
            ]);

            return null;
        }
    }

    /**
     * Registra un error u excepción del sistema en logs y auditoría con contexto completo.
     */
    public static function recordError(
        string $module,
        string $description,
        ?\Throwable $exception = null,
        ?Model $auditable = null,
        array $metadata = [],
        ?int $userId = null
    ): ?self {
        if ($exception) {
            $metadata['exception_class'] = get_class($exception);
            $metadata['exception_message'] = $exception->getMessage();
            $metadata['exception_file'] = $exception->getFile().':'.$exception->getLine();
        }

        return self::record(
            'system.error',
            $module,
            $description.($exception ? ' ('.$exception->getMessage().')' : ''),
            $auditable,
            $metadata,
            $userId
        );
    }
}
