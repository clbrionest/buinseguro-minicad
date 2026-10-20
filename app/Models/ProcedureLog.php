<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcedureLog extends Model
{
    protected $fillable = [
        'procedure_id', 
        'user_id', 
        'action_type', 
        'description', 
        'latitude', 
        'longitude'
    ];
    
    // Relación: Esta anotación pertenece a un procedimiento
    public function procedure()
    {
        return $this->belongsTo(Procedure::class);
    }

    // Relación: Esta anotación fue hecha por un usuario
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
