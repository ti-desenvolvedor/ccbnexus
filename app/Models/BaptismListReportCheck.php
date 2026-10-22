<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BaptismListReportCheck extends Model
{
    protected $fillable = [
        'baptism_list_report_id',
        'user_id',
        'checked_at',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(BaptismListReport::class, 'baptism_list_report_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
