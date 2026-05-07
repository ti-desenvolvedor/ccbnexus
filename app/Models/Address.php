<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Address extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'addressable_type',
        'addressable_id',
        'label',
        'postal_code',
        'country',
        'state',
        'city',
        'district',
        'street_line1',
        'street_line2',
        'number',
        'complement',
        'latitude',
        'longitude',
        'meta',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'meta' => 'array',
    ];

    public function addressable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
