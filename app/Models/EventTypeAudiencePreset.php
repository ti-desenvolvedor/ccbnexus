<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventTypeAudiencePreset extends Model
{
    protected $fillable = [
        'event_type_id',
        'invitation_paragraph',
        'report_layout',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function eventType(): BelongsTo
    {
        return $this->belongsTo(EventType::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(EventTypeAudiencePresetPosition::class);
    }
}
