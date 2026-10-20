<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sector extends Model
{
    // Permitimos que se guarde el nombre masivamente
    protected $fillable = ['name'];
    
    // Relación: Un sector tiene muchos procedimientos (1:N)
    public function procedures()
    {
        return $this->hasMany(Procedure::class);
    }

    // Relación: Un sector es vigilado por varios usuarios (Muchos a Muchos)
    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
