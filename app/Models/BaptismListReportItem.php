<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BaptismListReportItem extends Model
{
    protected $fillable = [
        'baptism_list_report_id',
        'section_key',
        'sort_order',
        'is_visible',
        'source_type',
        'source_id',
        'payload',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'sort_order' => 'integer',
        'payload' => 'array',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(BaptismListReport::class, 'baptism_list_report_id');
    }
}
