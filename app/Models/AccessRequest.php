<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AccessRequest extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'message',
        'regional_id',
        'administration_id',
        'status',
        'reviewed_by_user_id',
        'reviewed_at',
        'review_note',
        'provisioned_user_id',
        'meta',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'meta' => 'array',
    ];

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function regional(): BelongsTo
    {
        return $this->belongsTo(Regional::class);
    }

    public function administration(): BelongsTo
    {
        return $this->belongsTo(Administration::class);
    }

    public function provisionedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provisioned_user_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name', 'email', 'phone', 'regional_id', 'administration_id',
                'status', 'reviewed_by_user_id', 'reviewed_at', 'review_note', 'provisioned_user_id',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
