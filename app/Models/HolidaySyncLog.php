<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HolidaySyncLog extends Model
{
    protected $fillable = [
        'period',
        'status',
        'source',
        'years',
        'inserted',
        'updated',
        'message',
        'ran_at',
    ];

    protected $casts = [
        'years' => 'array',
        'inserted' => 'integer',
        'updated' => 'integer',
        'ran_at' => 'datetime',
    ];
}
