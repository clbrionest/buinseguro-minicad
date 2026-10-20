<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperationalEvent extends Model
{
    protected $fillable = [
        'name',
        'event_type',
        'description',
        'is_active',
        'user_id',
        'closed_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'closed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reports()
    {
        return $this->hasMany(EventReport::class);
    }
}
