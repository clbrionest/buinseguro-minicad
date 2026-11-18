<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class PreventivePatrol extends Model
{
    protected $fillable = [
        'folio',
        'device_id',
        'sector_id',
        'user_id',
        'patrol_assignment_id',
        'patrol_type',
        'status',
        'started_at',
        'ended_at',
        'start_odometer',
        'end_odometer',
        'mission_objective',
        'completion_notes',
        'gps_route',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'gps_route' => 'array',
    ];

    public static function generateNextFolio(): string
    {
        $year = Carbon::now()->format('Y');
        $last = self::whereYear('created_at', $year)->latest('id')->first();
        $nextNumber = $last ? ($last->id + 1) : 1;

        return sprintf('PAT-%s-%04d', $year, $nextNumber);
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function sector()
    {
        return $this->belongsTo(Sector::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function patrolAssignment()
    {
        return $this->belongsTo(PatrolAssignment::class);
    }

    public function checkpoints()
    {
        return $this->hasMany(PreventivePatrolCheckpoint::class)->orderBy('order');
    }

    public function procedures()
    {
        return $this->hasMany(Procedure::class, 'preventive_patrol_id');
    }

    public function getDurationMinutesAttribute(): ?int
    {
        if (! $this->started_at) {
            return null;
        }

        $end = $this->ended_at ?? now();

        return (int) round($this->started_at->diffInMinutes($end));
    }

    public function getDurationFormattedAttribute(): string
    {
        $mins = $this->duration_minutes;
        if (is_null($mins)) {
            return 'N/A';
        }

        $hours = intdiv($mins, 60);
        $remainder = $mins % 60;

        if ($hours > 0) {
            return "{$hours}h {$remainder}m";
        }

        return "{$remainder}m";
    }

    public function getValidatedCheckpointsCountAttribute(): int
    {
        return $this->checkpoints->where('status', 'Validado')->count();
    }

    public function getTotalCheckpointsCountAttribute(): int
    {
        return $this->checkpoints->count();
    }

    public function getValidationPercentageAttribute(): int
    {
        $total = $this->total_checkpoints_count;
        if ($total === 0) {
            return 100;
        }

        return (int) round(($this->validated_checkpoints_count / $total) * 100);
    }

    public function recordGpsPoint($lat, $lng, $speed = null): void
    {
        if (is_null($lat) || is_null($lng)) {
            return;
        }

        $route = $this->gps_route ?: [];
        $route[] = [
            'lat' => (float) $lat,
            'lng' => (float) $lng,
            'recorded_at' => now()->toIso8601String(),
            'speed' => $speed,
        ];

        $this->update(['gps_route' => $route]);
    }
}
