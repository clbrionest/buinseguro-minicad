<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use App\Models\Call;
use App\Models\AuditLog;

class CallAudioController extends Controller
{
    public function play(Call $call)
    {
        $user = auth()->user();

        if (!$user) {
            abort(401);
        }

        // Control de Acceso basado en Roles y Anexo:
        // - Administrador y Supervisor pueden escuchar todas las grabaciones.
        // - Operador solo puede escuchar grabaciones de su propio anexo telefónico.
        // - Otros roles (ej. Inspector) no tienen acceso a grabaciones PBX.
        $canListen = $user->hasAnyRole(['Administrador', 'Supervisor'])
            || ($user->hasRole('Operador') && !empty($user->extension) && (string) $call->extension === (string) $user->extension);

        if (!$canListen) {
            AuditLog::record(
                'security.denied',
                'Telefonía PBX',
                "Intento no autorizado de reproducir audio de llamada #{$call->id} (Anexo {$call->extension}).",
                $call
            );
            abort(403, 'No tienes autorización para reproducir grabaciones de este anexo.');
        }

        // Prevención de Path Traversal y rutas vacías
        $path = trim((string) $call->recording_path);
        if ($path === '' || str_contains($path, '..')) {
            abort(404, 'Ruta de grabación inválida o inexistente.');
        }

        // Verificamos si existe el archivo en el disco de grabaciones
        if (!Storage::disk('asterisk_recordings')->exists($path)) {
            abort(404, 'Grabación no encontrada en el servidor PBX.');
        }

        AuditLog::record(
            'call.audio_played',
            'Telefonía PBX',
            "Reproducción de audio de llamada #{$call->id} (Origen: {$call->caller_id}, Anexo: {$call->extension}).",
            $call
        );

        // Retornamos el archivo como un Stream para que el navegador lo reproduzca
        return Storage::disk('asterisk_recordings')->response($path);
    }
}