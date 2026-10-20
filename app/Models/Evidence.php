<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evidence extends Model
{
    // Cuidado: el nombre de la tabla en tu migración fue 'evidences'
    protected $table = 'evidence';

    protected $fillable = [
        'procedure_id',
        'user_id',
        'file_path',
    ];

    public function procedure()
    {
        return $this->belongsTo(Procedure::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}