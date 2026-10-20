<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Call;
use App\Events\NewCallIncoming;
use Illuminate\Http\Request;

class CallWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Validación de token de seguridad (si ASTERISK_WEBHOOK_TOKEN está configurado en .env)
        $configuredToken = config('services.asterisk.webhook_token');
        if (!empty($configuredToken)) {
            $providedToken = $request->header('X-Asterisk-Token') ?? $request->input('token');
            if (!hash_equals((string) $configuredToken, (string) $providedToken)) {
                return response()->json(['error' => 'Unauthorized webhook request'], 401);
            }
        }

        // Limpiamos el ID por si Asterisk manda espacios en blanco
        $asteriskId = trim((string) $request->id);
        if ($asteriskId === '') {
            return response()->json(['error' => 'Missing call id'], 422);
        }

        // Validamos que la ruta de grabación traiga archivo real, no solo carpeta de fecha y sin saltos de directorio (..)
        $rawPath = trim((string) $request->recording_path);
        $validRecordingPath = ($request->filled('recording_path') && strlen($rawPath) > 12 && !str_contains($rawPath, '..'))
            ? $rawPath
            : null;

        // 1. Buscamos si la llamada ya existe
        $call = Call::where('asterisk_id', $asteriskId)->first();

        if ($call) {
            // SI EXISTE: Solo actualizamos el estado y la ruta de grabación (si es válida)
            $call->update([
                'status' => $request->status ?? $call->status,
                'recording_path' => $validRecordingPath ?? $call->recording_path,
            ]);
        } else {
            // NO EXISTE: Es una llamada nueva (Ringing)
            $call = Call::create([
                'asterisk_id' => $asteriskId,
                'caller_id' => $request->numero ?? 'Desconocido',
                'extension' => $request->extension ?? '0',
                'status' => $request->status ?? 'ringing',
                'recording_path' => $validRecordingPath,
            ]);

            // SOLO lanzamos el evento si es una creación real (Ringing)
            // y nos aseguramos de que el estado sea 'ringing'
            if ($call->status === 'ringing') {
                broadcast(new \App\Events\NewCallIncoming($call));
            }
        }

        return response()->json(['success' => true]);
    }
}