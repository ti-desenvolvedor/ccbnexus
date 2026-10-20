<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class RegionalNotice extends Model
{
    use LogsActivity;

    protected $fillable = [
        'regional_id',
        'title',
        'body',
        'category',
        'valid_from',
        'valid_until',
        'is_active',
        'is_standing',
        'sort_order',
        'meta',
    ];

    protected $casts = [
        'valid_from' => 'date',
        'valid_until' => 'date',
        'is_active' => 'boolean',
        'is_standing' => 'boolean',
        'sort_order' => 'integer',
        'meta' => 'array',
    ];

    public function regional(): BelongsTo
    {
        return $this->belongsTo(Regional::class);
    }

    public function isValidOn($date): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->is_standing) {
            return true;
        }

        $d = Carbon::parse($date)->startOfDay();

        return $d->betweenIncluded($this->valid_from->startOfDay(), $this->valid_until->endOfDay());
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'category', 'valid_from', 'valid_until', 'is_active', 'is_standing', 'sort_order'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
