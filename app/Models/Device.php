<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $fillable = [
        'name',
        'operational_status',
        'status_notes',
        'last_lat',
        'last_lng',
        'last_location_at',
        'gps_route',
    ];

    protected $casts = [
        'last_location_at' => 'datetime',
        'gps_route' => 'array',
    ];

    public function procedures()
    {
        return $this->hasMany(Procedure::class);
    }

    public function activeProcedures()
    {
        return $this->hasMany(Procedure::class)->whereIn('status', ['En Camino', 'En el Lugar', 'Derivado']);
    }

    public function activePatrolAssignment()
    {
        return $this->hasOne(PatrolAssignment::class)->where('is_active', true)->latestOfMany();
    }

    public function activePreventivePatrol()
    {
        return $this->hasOne(PreventivePatrol::class)->where('status', 'En Curso')->latestOfMany('started_at');
    }

    public function getEffectiveStatusAttribute(): string
    {
        $activeCount = $this->active_procedures_count ?? $this->activeProcedures()->count();
        $normalized = trim(str_replace(' (QAP)', '', (string) ($this->operational_status ?: 'Disponible')));
        if ($activeCount > 0 && ($normalized === 'Disponible' || $normalized === '')) {
            return 'En Procedimiento';
        }

        return $this->operational_status ?: 'Disponible';
    }

    /**
     * Registra un nuevo punto GPS en el móvil y en su patrullaje preventivo activo (si existe).
     */
    public function recordGpsPoint($lat, $lng, $speed = null): void
    {
        if (is_null($lat) || is_null($lng) || $lat === '' || $lng === '') {
            return;
        }

        $numLat = round((float) $lat, 7);
        $numLng = round((float) $lng, 7);
        $todayPrefix = now()->format('Y-m-d');

        // Filtrar puntos del día actual para mantener una traza limpia del turno/jornada
        $existingRoute = is_array($this->gps_route) ? $this->gps_route : [];
        $todayRoute = array_values(array_filter($existingRoute, function ($pt) use ($todayPrefix) {
            if (! is_array($pt) || ! isset($pt['lat'], $pt['lng'])) {
                return false;
            }
            if (empty($pt['recorded_at'])) {
                return true;
            }

            return str_starts_with((string) $pt['recorded_at'], $todayPrefix);
        }));

        // Evitar duplicar coordenadas idénticas consecutivas (< 2 metros)
        $lastPoint = end($todayRoute);
        $shouldAppend = true;
        if ($lastPoint && isset($lastPoint['lat'], $lastPoint['lng'])) {
            $dist = Procedure::calculateDistanceMeters($lastPoint['lat'], $lastPoint['lng'], $numLat, $numLng);
            if (! is_null($dist) && $dist < 2) {
                $shouldAppend = false;
            }
        }

        if ($shouldAppend) {
            $todayRoute[] = [
                'lat' => $numLat,
                'lng' => $numLng,
                'recorded_at' => now()->toIso8601String(),
                'speed' => is_numeric($speed) ? round((float) $speed, 1) : null,
            ];
        }

        // Conservar los últimos 200 puntos de la ruta para un rendimiento óptimo en el mapa
        if (count($todayRoute) > 200) {
            $todayRoute = array_slice($todayRoute, -200);
        }

        $this->update([
            'last_lat' => $numLat,
            'last_lng' => $numLng,
            'last_location_at' => now(),
            'gps_route' => $todayRoute,
        ]);

        // Registrar también en el patrullaje preventivo en curso si existe
        $activePatrol = PreventivePatrol::where('device_id', $this->id)
            ->where('status', 'En Curso')
            ->latest('started_at')
            ->first();

        if ($activePatrol) {
            $activePatrol->recordGpsPoint($numLat, $numLng, $speed);
        }
    }
}
