<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class RoomAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_room_id',
        'assignable_type',
        'assignable_id',
    ];

    public function meetingRoom(): BelongsTo
    {
        return $this->belongsTo(MeetingRoom::class);
    }

    public function assignable(): MorphTo
    {
        return $this->morphTo();
    }
}
