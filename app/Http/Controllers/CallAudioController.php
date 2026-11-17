<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Call;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class CallAudioController extends Controller
{
    public function play(Call $call)
    {
        $user = auth()->user();

        if (! $user) {
            abort(401);
        }

        // Control de Acceso basado en Roles y Anexo:
        // - Administrador y Supervisor pueden escuchar todas las grabaciones.
        // - Operador solo puede escuchar grabaciones de su propio anexo telefónico.
        // - Otros roles (ej. Inspector) no tienen acceso a grabaciones PBX.
        $canListen = $user->hasAnyRole(['Administrador', 'Supervisor'])
            || ($user->hasRole('Operador') && ! empty($user->extension) && (string) $call->extension === (string) $user->extension);

        if (! $canListen) {
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
            AuditLog::record(
                'security.path_traversal',
                'Telefonía PBX',
                "Intento de acceso a ruta inválida o Path Traversal en audio de llamada #{$call->id}: '{$path}'.",
                $call
            );

            abort(404, 'Ruta de grabación inválida o inexistente.');
        }

        try {
            // Normalizamos la ruta removiendo prefijos absolutos de monitor si estuvieran presentes
            $normalizedPath = preg_replace('#^(/var/spool/asterisk)?/monitor/#', '', $path);
            $normalizedPath = ltrim((string) $normalizedPath, '/');

            $disk = Storage::disk('asterisk_recordings');
            $resolvedPath = $disk->exists($normalizedPath) ? $normalizedPath : ($disk->exists($path) ? $path : null);

            if (! $resolvedPath) {
                AuditLog::record(
                    'call.audio_not_found',
                    'Telefonía PBX',
                    "Grabación no encontrada en servidor SFTP para llamada #{$call->id} (Ruta: '{$path}').",
                    $call
                );

                abort(404, 'Grabación no encontrada en el servidor PBX.');
            }

            AuditLog::record(
                'call.audio_played',
                'Telefonía PBX',
                "Reproducción de audio de llamada #{$call->id} (Origen: {$call->caller_id}, Anexo: {$call->extension}).",
                $call
            );

            // Retornamos el archivo como un Stream para que el navegador lo reproduzca
            return $disk->response($resolvedPath);
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (\Throwable $e) {
            AuditLog::recordError(
                'Telefonía PBX',
                "Fallo crítico al conectar o transmitir audio SFTP para llamada #{$call->id}",
                $e,
                $call
            );

            abort(500, 'Error de comunicación con el almacenamiento de telefonía PBX.');
        }
    }
}
