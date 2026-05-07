<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class RoomReservation extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'meeting_room_id',
        'title',
        'notes',
        'starts_at',
        'ends_at',
        'status',
        'requires_approval',
        'requested_by_user_id',
        'approved_by_user_id',
        'approved_at',
        'moderator_note',
        'meta',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'approved_at' => 'datetime',
        'requires_approval' => 'boolean',
        'meta' => 'array',
    ];

    public function meetingRoom(): BelongsTo
    {
        return $this->belongsTo(MeetingRoom::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'meeting_room_id',
                'title',
                'starts_at',
                'ends_at',
                'status',
                'requires_approval',
                'requested_by_user_id',
                'approved_by_user_id',
                'approved_at',
                'moderator_note',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
