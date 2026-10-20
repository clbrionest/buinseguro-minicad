<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Origin extends Model
{
    // Permitimos que se guarde el nombre masivamente
    protected $fillable = ['name'];
}
