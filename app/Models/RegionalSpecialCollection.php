<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class RegionalSpecialCollection extends Model
{
    use LogsActivity;

    public const MODE_PROGRAMADA = 'programada';

    public const MODE_URGENTE = 'urgente';

    protected $fillable = [
        'regional_id',
        'mode',
        'title',
        'body',
        'collection_on',
        'collection_until',
        'valid_from',
        'valid_until',
        'is_active',
        'meta',
    ];

    protected $casts = [
        'collection_on' => 'date',
        'collection_until' => 'date',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    public function regional(): BelongsTo
    {
        return $this->belongsTo(Regional::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'mode', 'valid_from', 'valid_until', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
