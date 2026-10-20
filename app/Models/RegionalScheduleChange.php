<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class RegionalScheduleChange extends Model
{
    use LogsActivity;

    protected $fillable = [
        'regional_id',
        'change_type',
        'location_id',
        'title',
        'body',
        'old_value',
        'new_value',
        'effective_from',
        'effective_until',
        'valid_from',
        'valid_until',
        'is_active',
        'meta',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_until' => 'date',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    public function regional(): BelongsTo
    {
        return $this->belongsTo(Regional::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'change_type', 'valid_from', 'valid_until', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
