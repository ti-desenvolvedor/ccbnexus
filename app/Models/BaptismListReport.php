<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class BaptismListReport extends Model
{
    use LogsActivity;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_READY = 'ready';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'regional_id',
        'issue_date',
        'period_start',
        'period_end',
        'revision',
        'edition_at',
        'status',
        'generated_by',
        'generated_at',
        'meta',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'edition_at' => 'datetime',
        'generated_at' => 'datetime',
        'revision' => 'integer',
        'meta' => 'array',
    ];

    public function regional(): BelongsTo
    {
        return $this->belongsTo(Regional::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BaptismListReportItem::class)->orderBy('sort_order');
    }

    public function checks(): HasMany
    {
        return $this->hasMany(BaptismListReportCheck::class)->orderBy('checked_at');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['issue_date', 'revision', 'status', 'period_start', 'period_end'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
