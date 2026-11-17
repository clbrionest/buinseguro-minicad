<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreventivePatrolCheckpoint extends Model
{
    protected $fillable = [
        'preventive_patrol_id',
        'landmark_id',
        'name',
        'address',
        'latitude',
        'longitude',
        'order',
        'status',
        'validated_at',
        'validated_by_user_id',
        'validation_lat',
        'validation_lng',
        'validation_distance_meters',
        'notes',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'validation_lat' => 'float',
        'validation_lng' => 'float',
        'validated_at' => 'datetime',
    ];

    public function preventivePatrol()
    {
        return $this->belongsTo(PreventivePatrol::class);
    }

    public function landmark()
    {
        return $this->belongsTo(Landmark::class);
    }

    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by_user_id');
    }

    public function validateCheckpoint(?float $lat = null, ?float $lng = null, ?int $userId = null, ?string $notes = null): void
    {
        $distance = null;
        if (! is_null($lat) && ! is_null($lng) && ! is_null($this->latitude) && ! is_null($this->longitude)) {
            $distance = Procedure::calculateDistanceMeters(
                $this->latitude,
                $this->longitude,
                $lat,
                $lng
            );
        }

        $this->update([
            'status' => 'Validado',
            'validated_at' => now(),
            'validated_by_user_id' => $userId ?? auth()->id(),
            'validation_lat' => $lat,
            'validation_lng' => $lng,
            'validation_distance_meters' => $distance,
            'notes' => $notes ?: $this->notes,
        ]);
    }

    public function omitCheckpoint(?string $notes = null): void
    {
        $this->update([
            'status' => 'Omitido',
            'notes' => $notes ?: $this->notes,
        ]);
    }
}
