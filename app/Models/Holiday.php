<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = [
        'observed_on',
        'name',
        'scope',
        'state',
        'source_key',
    ];

    protected $casts = [
        'observed_on' => 'date',
    ];
}
