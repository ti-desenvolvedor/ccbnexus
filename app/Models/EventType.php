<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EventType extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'name',
        'slug',
        'is_active',
        'sort_order',
        'meta',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    public function audiencePreset(): HasOne
    {
        return $this->hasOne(EventTypeAudiencePreset::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'slug', 'is_active', 'sort_order'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
