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
    ];

    protected $casts = [
        'last_location_at' => 'datetime',
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

    public function getEffectiveStatusAttribute(): string
    {
        $activeCount = $this->active_procedures_count ?? $this->activeProcedures()->count();
        if ($activeCount > 0 && ($this->operational_status === 'Disponible (QAP)' || empty($this->operational_status))) {
            return 'En Procedimiento';
        }
        return $this->operational_status ?: 'Disponible (QAP)';
    }
}
