<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Parking extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'location_id',
        'name',
        'capacity',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['location_id', 'name', 'capacity'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
