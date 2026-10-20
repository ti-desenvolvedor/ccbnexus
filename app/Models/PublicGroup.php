<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PublicGroup extends Model
{
    use LogsActivity;

    protected $table = 'public_groups';

    protected $fillable = [
        'regional_id',
        'name',
        'slug',
        'is_active',
        'sort_order',
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

    public function subgroups(): HasMany
    {
        return $this->hasMany(PublicSubgroup::class, 'public_group_id')->orderBy('sort_order');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['regional_id', 'name', 'slug', 'is_active', 'sort_order'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
