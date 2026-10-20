<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventCategory extends Model
{
    protected $fillable = [
        'name',
        'icon',
        'color',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getBadgeClassesAttribute(): string
    {
        return match ($this->color) {
            'blue'   => 'bg-blue-100 text-blue-800 border-blue-300',
            'green'  => 'bg-green-100 text-green-800 border-green-300',
            'yellow' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
            'orange' => 'bg-orange-100 text-orange-800 border-orange-300',
            'red'    => 'bg-red-100 text-red-800 border-red-300',
            'purple' => 'bg-purple-100 text-purple-800 border-purple-300',
            default  => 'bg-gray-100 text-gray-800 border-gray-300',
        };
    }
}
