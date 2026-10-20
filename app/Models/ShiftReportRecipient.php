<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftReportRecipient extends Model
{
    protected $fillable = [
        'name',
        'email',
        'role_title',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
