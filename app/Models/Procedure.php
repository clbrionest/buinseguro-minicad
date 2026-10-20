<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Procedure extends Model
{
    protected $fillable = [
        'user_id', 
        'origin_id', 
        'sector_id', 
        'procedure_type_id',
        'initial_procedure_type_id',
        'device_id',
        'exact_address', 
        'address_reference',
        'metadata',
        'caller_name', 
        'caller_id_document', 
        'caller_phone', 
        'status',
        'dispatched_at',
        'mobile_arrived_at',
        'mobile_arrival_lat',
        'mobile_arrival_lng',
        'mobile_arrival_distance',
        'arrived_at',
        'closed_at',
        'landmark_id',
        'formatted_address',
        'latitude',
        'longitude',
        'is_preventive',
        'patrol_assignment_id',
        'parent_id', // <-- Nuevo campo para vincular casos
        'call_id', //<-- Nuevo campo para vincular con la llamada de Asterisk
    ];

    protected $casts = [
        'dispatched_at' => 'datetime',
        'mobile_arrived_at' => 'datetime',
        'arrived_at' => 'datetime',
        'closed_at' => 'datetime',
        'is_preventive' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Calcula la distancia en metros entre dos coordenadas GPS (Fórmula de Haversine)
     */
    public static function calculateDistanceMeters($lat1, $lon1, $lat2, $lon2): ?int
    {
        if (is_null($lat1) || is_null($lon1) || is_null($lat2) || is_null($lon2) || $lat1 === '' || $lon1 === '') {
            return null;
        }

        $earthRadius = 6371000; // Radio de la Tierra en metros
        $dLat = deg2rad((float) $lat2 - (float) $lat1);
        $dLon = deg2rad((float) $lon2 - (float) $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad((float) $lat1)) * cos(deg2rad((float) $lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return (int) round($earthRadius * $c);
    }

    /**
     * Minutos de despacho (Desde creación hasta despacho, o hasta ahora si sigue Pendiente)
     */
    public function getDispatchMinutesAttribute(): ?int
    {
        if (!$this->created_at) {
            return null;
        }
        $end = $this->dispatched_at ?? ($this->status === 'Pendiente' ? now() : null);
        return $end ? (int) round($this->created_at->diffInMinutes($end)) : null;
    }

    /**
     * Minutos de arribo al lugar (Desde despacho/creación hasta llegada confirmada, o hasta ahora si sigue En Camino)
     */
    public function getArrivalMinutesAttribute(): ?int
    {
        $start = $this->dispatched_at ?? $this->created_at;
        if (!$start) {
            return null;
        }
        $end = $this->arrived_at ?? $this->mobile_arrived_at ?? ($this->status === 'En Camino' ? now() : null);
        return $end ? (int) round($start->diffInMinutes($end)) : null;
    }

    /**
     * Semáforo SLA para mostrar en tablas y fichas operativas
     */
    public function getSlaTrafficLightAttribute(): array
    {
        if ($this->status === 'Pendiente') {
            $mins = (int) round($this->created_at->diffInMinutes(now()));
            if ($mins < 5) {
                return ['color' => 'green', 'label' => "{$mins}m espera", 'badge' => 'bg-green-100 text-green-800 border-green-300', 'dot' => 'bg-green-500'];
            } elseif ($mins <= 15) {
                return ['color' => 'yellow', 'label' => "{$mins}m espera", 'badge' => 'bg-yellow-100 text-yellow-800 border-yellow-300', 'dot' => 'bg-yellow-500'];
            }
            return ['color' => 'red', 'label' => "{$mins}m s/despacho", 'badge' => 'bg-red-100 text-red-800 border-red-300 animate-pulse', 'dot' => 'bg-red-600'];
        }

        if ($this->status === 'En Camino') {
            $start = $this->dispatched_at ?? $this->created_at;
            $mins = (int) round($start->diffInMinutes(now()));
            if ($mins <= 10) {
                return ['color' => 'green', 'label' => "{$mins}m en ruta", 'badge' => 'bg-green-100 text-green-800 border-green-300', 'dot' => 'bg-green-500'];
            } elseif ($mins <= 20) {
                return ['color' => 'yellow', 'label' => "{$mins}m en ruta", 'badge' => 'bg-yellow-100 text-yellow-800 border-yellow-300', 'dot' => 'bg-yellow-500'];
            }
            return ['color' => 'red', 'label' => "{$mins}m en ruta", 'badge' => 'bg-red-100 text-red-800 border-red-300 animate-pulse', 'dot' => 'bg-red-600'];
        }

        // En el Lugar, Derivado o Cerrado: evaluamos cuánto tardó en llegar
        $arrivalMins = $this->arrival_minutes;
        if (!is_null($arrivalMins)) {
            if ($arrivalMins <= 12) {
                return ['color' => 'green', 'label' => "Arribo: {$arrivalMins}m", 'badge' => 'bg-green-100 text-green-800 border-green-300', 'dot' => 'bg-green-500'];
            } elseif ($arrivalMins <= 20) {
                return ['color' => 'yellow', 'label' => "Arribo: {$arrivalMins}m", 'badge' => 'bg-yellow-100 text-yellow-800 border-yellow-300', 'dot' => 'bg-yellow-500'];
            }
            return ['color' => 'red', 'label' => "Arribo: {$arrivalMins}m", 'badge' => 'bg-red-100 text-red-800 border-red-300', 'dot' => 'bg-red-600'];
        }

        $totalMins = (int) round($this->created_at->diffInMinutes($this->closed_at ?? now()));
        return ['color' => 'gray', 'label' => "{$totalMins}m total", 'badge' => 'bg-gray-100 text-gray-700 border-gray-300', 'dot' => 'bg-gray-400'];
    }

    // Relaciones de pertenencia (Pertenece a...)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function origin()
    {
        return $this->belongsTo(Origin::class);
    }

    public function sector()
    {
        return $this->belongsTo(Sector::class);
    }

    public function procedureType()
    {
        return $this->belongsTo(ProcedureType::class);
    }

    public function initialProcedureType()
    {
        return $this->belongsTo(ProcedureType::class, 'initial_procedure_type_id');
    }

    /**
     * Devuelve los metadatos con sus etiquetas legibles para vistas, tablas y fichas PDF
     */
    public function getFormattedMetadataAttribute(): array
    {
        if (empty($this->metadata) || !is_array($this->metadata)) {
            return [];
        }

        $schemaFields = $this->procedureType ? collect($this->procedureType->effective_meta_fields)->keyBy('key') : collect();
        $result = [];

        foreach ($this->metadata as $key => $val) {
            if ($val === '' || is_null($val)) {
                continue;
            }
            $label = $schemaFields->has($key)
                ? $schemaFields[$key]['label']
                : ucwords(str_replace('_', ' ', $key));

            $result[] = [
                'key' => $key,
                'label' => $label,
                'value' => $val,
            ];
        }

        return $result;
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    // Relación con su historial/bitácora (Tiene muchos...)
    public function logs()
    {
        return $this->hasMany(ProcedureLog::class);
    }
    
    public function evidences()
    {
        return $this->hasMany(Evidence::class);
    }

    public function patrolAssignment()
    {
        return $this->belongsTo(PatrolAssignment::class);
    }

    // Relación hacia arriba: El caso principal al que este fue vinculado
    public function parent()
    {
        return $this->belongsTo(Procedure::class, 'parent_id');
    }

    // Relación hacia abajo: Todos los casos que se han vinculado a este
    public function children()
    {
        return $this->hasMany(Procedure::class, 'parent_id');
    }

    public function call()
    {
        // Un procedimiento pertenece a una llamada (si es que vino de una)
        return $this->belongsTo(Call::class, 'call_id');
    }
}
