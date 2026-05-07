<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class NotificationRuleTemplate extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'name',
        'days_before',
        'is_active',
        'sort_order',
        'meta',
    ];

    protected $casts = [
        'days_before' => 'integer',
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'days_before', 'is_active', 'sort_order'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
