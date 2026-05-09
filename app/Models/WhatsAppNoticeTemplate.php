<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class WhatsAppNoticeTemplate extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $table = 'whatsapp_notice_templates';

    protected $fillable = [
        'regional_id',
        'name',
        'slug',
        'body',
        'is_active',
        'is_default',
        'created_by',
    ];

    protected $casts = [
        'regional_id' => 'integer',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'created_by' => 'integer',
    ];

    public function regional(): BelongsTo
    {
        return $this->belongsTo(Regional::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function notices(): HasMany
    {
        return $this->hasMany(WhatsAppNotice::class, 'template_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['regional_id', 'name', 'slug', 'is_active', 'is_default'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}

