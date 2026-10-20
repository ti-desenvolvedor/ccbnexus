<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventTypeAudiencePresetPosition extends Model
{
    protected $fillable = [
        'event_type_audience_preset_id',
        'position_slug',
    ];

    public function preset(): BelongsTo
    {
        return $this->belongsTo(EventTypeAudiencePreset::class, 'event_type_audience_preset_id');
    }
}
