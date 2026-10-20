<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Event extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'regional_id',
        'title',
        'description',
        'event_type_id',
        'starts_at',
        'ends_at',
        'location_id',
        'meeting_room_id',
        'attendance_mode',
        'dress_code',
        'whatsapp_enabled',
        'include_on_baptism_list',
        'has_libras',
        'whatsapp_notice_template_id',
        'expected_attendees',
        'needs_sound_controller',
        'needs_av',
        'needs_parking',
        'needs_meals',
        'meal_coffee',
        'meal_lunch',
        'meal_snack',
        'meal_dinner',
        'needs_nursing',
        'needs_valet',
        'needs_other_materials',
        'other_materials_note',
        'status',
        'created_by',
        'parent_event_id',
        'is_occurrence',
        'recurrence_frequency',
        'recurrence_interval_weeks',
        'recurrence_config',
        'recurrence_until',
        'cancellation_reason',
        'cancelled_at',
        'cancelled_by',
        'meta',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_occurrence' => 'boolean',
        'recurrence_until' => 'date',
        'cancelled_at' => 'datetime',
        'meta' => 'array',
        'whatsapp_enabled' => 'boolean',
        'include_on_baptism_list' => 'boolean',
        'has_libras' => 'boolean',
        'needs_sound_controller' => 'boolean',
        'needs_av' => 'boolean',
        'needs_parking' => 'boolean',
        'needs_meals' => 'boolean',
        'meal_coffee' => 'boolean',
        'meal_lunch' => 'boolean',
        'meal_snack' => 'boolean',
        'meal_dinner' => 'boolean',
        'needs_nursing' => 'boolean',
        'needs_valet' => 'boolean',
        'needs_other_materials' => 'boolean',
        'expected_attendees' => 'integer',
        'recurrence_interval_weeks' => 'integer',
        'recurrence_config' => 'array',
    ];

    public function regional(): BelongsTo
    {
        return $this->belongsTo(Regional::class);
    }

    public function eventType(): BelongsTo
    {
        return $this->belongsTo(EventType::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function meetingRoom(): BelongsTo
    {
        return $this->belongsTo(MeetingRoom::class);
    }

    public function whatsappNoticeTemplate(): BelongsTo
    {
        return $this->belongsTo(WhatsAppNoticeTemplate::class, 'whatsapp_notice_template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_event_id');
    }

    public function occurrences(): HasMany
    {
        return $this->hasMany(self::class, 'parent_event_id');
    }

    public function audiences(): BelongsToMany
    {
        return $this->belongsToMany(Audience::class, 'audience_event', 'event_id', 'audience_id')->withTimestamps();
    }

    public function publicPositions(): BelongsToMany
    {
        return $this->belongsToMany(PublicPosition::class, 'event_public_position', 'event_id', 'public_position_id')
            ->withTimestamps();
    }

    public function roleAssignments(): HasMany
    {
        return $this->hasMany(EventRoleAssignment::class);
    }

    public function whatsappGroups(): BelongsToMany
    {
        return $this->belongsToMany(
            WhatsAppGroup::class,
            'event_whatsapp_group',
            'event_id',
            'whatsapp_group_id'
        )->withTimestamps();
    }

    public function attendantLabel(): ?string
    {
        $assignment = $this->roleAssignments
            ->first(fn (EventRoleAssignment $a) => $a->roleTemplate?->slug === 'atende')
            ?? $this->roleAssignments->first();

        if (! $assignment) {
            return null;
        }

        return $assignment->assignee_label ?: $assignment->user?->name;
    }

    public function effectiveStatus(): string
    {
        if (in_array($this->status, ['cancelled', 'rejected'], true)) {
            return $this->status;
        }

        if ($this->parent_event_id) {
            return $this->parent?->status ?? $this->status;
        }

        return (string) $this->status;
    }

    public function agendaEditId(): int
    {
        return ($this->is_occurrence && $this->parent_event_id) ? (int) $this->parent_event_id : (int) $this->id;
    }

    public function effectiveStatusLabel(): string
    {
        return match ($this->effectiveStatus()) {
            'draft' => 'Rascunho',
            'pending_approval' => 'Pendente',
            'published' => 'Publicado',
            'cancelled' => 'Cancelado',
            'rejected' => 'Rejeitado',
            default => 'Rascunho',
        };
    }

    public function effectiveStatusTone(): string
    {
        return match ($this->effectiveStatus()) {
            'published' => 'success',
            'pending_approval' => 'warning',
            'cancelled', 'rejected' => 'danger',
            default => 'neutral',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'draft' => 'Rascunho',
            'pending_approval' => 'Pendente',
            'published' => 'Publicado',
            'cancelled' => 'Cancelado',
            'rejected' => 'Rejeitado',
            default => 'Rascunho',
        };
    }

    public function statusTone(): string
    {
        return match ($this->status) {
            'published' => 'success',
            'pending_approval' => 'warning',
            'cancelled', 'rejected' => 'danger',
            default => 'neutral',
        };
    }

    public function notificationDispatches(): HasMany
    {
        return $this->hasMany(EventNotificationDispatch::class);
    }

    public function approvals(): MorphMany
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

    public function rsvps(): HasMany
    {
        return $this->hasMany(EventRsvp::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'regional_id', 'title', 'event_type_id', 'starts_at', 'ends_at',
                'location_id', 'meeting_room_id', 'attendance_mode', 'expected_attendees',
                'needs_sound_controller', 'needs_av', 'needs_parking', 'needs_meals',
                'meal_coffee', 'meal_lunch', 'meal_snack', 'meal_dinner',
                'needs_nursing', 'needs_valet', 'needs_other_materials',
                'recurrence_frequency', 'recurrence_interval_weeks', 'recurrence_config', 'recurrence_until',
                'status', 'cancellation_reason',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
