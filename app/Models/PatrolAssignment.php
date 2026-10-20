<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatrolAssignment extends Model
{
    protected $fillable = ['date', 'shift_name', 'device_id', 'sector_id', 'mission_notes', 'is_active'];

    public function device() { return $this->belongsTo(Device::class); }
    public function sector() { return $this->belongsTo(Sector::class); }
    
    // Aquí usamos withPivot para poder acceder al rol y horario de cada inspector
    public function crew() {
        return $this->belongsToMany(User::class, 'patrol_assignment_user')
                    ->withPivot('role', 'time_exception')
                    ->withTimestamps();
    }
}