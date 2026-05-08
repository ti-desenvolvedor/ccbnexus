<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class WhatsAppNotice extends Model
{
    use HasFactory;
    use LogsActivity;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT_MANUAL = 'sent_manual';

    protected $fillable = [
        'event_id',
        'template_id',
        'regional_id',
        'title',
        'body_final',
        'status',
        'sent_at',
        'sent_by',
    ];

    protected $casts = [
        'event_id' => 'integer',
        'template_id' => 'integer',
        'regional_id' => 'integer',
        'sent_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(WhatsAppNoticeTemplate::class, 'template_id');
    }

    public function regional(): BelongsTo
    {
        return $this->belongsTo(Regional::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['event_id', 'template_id', 'regional_id', 'status', 'sent_at', 'sent_by'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}

