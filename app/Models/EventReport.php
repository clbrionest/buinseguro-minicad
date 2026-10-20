<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventReport extends Model
{
    protected $fillable = [
        'operational_event_id',
        'user_id',
        'category',
        'urgency',
        'sector_id',
        'exact_address',
        'address_reference',
        'latitude',
        'longitude',
        'caller_name',
        'caller_phone',
        'device_id',
        'status',
        'notes',
        'dispatched_at',
        'resolved_at',
    ];

    protected $casts = [
        'dispatched_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(OperationalEvent::class, 'operational_event_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sector()
    {
        return $this->belongsTo(Sector::class);
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
