<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class WhatsAppGroup extends Model
{
    use LogsActivity;

    protected $table = 'whatsapp_groups';

    protected $fillable = [
        'regional_id',
        'name',
        'slug',
        'invite_hint',
        'is_active',
        'meta',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    public function regional(): BelongsTo
    {
        return $this->belongsTo(Regional::class);
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(
            Event::class,
            'event_whatsapp_group',
            'whatsapp_group_id',
            'event_id'
        )->withTimestamps();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'slug', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
