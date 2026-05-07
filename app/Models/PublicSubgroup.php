<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PublicSubgroup extends Model
{
    protected $table = 'public_subgroups';

    protected $fillable = [
        'public_group_id',
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

    public function group(): BelongsTo
    {
        return $this->belongsTo(PublicGroup::class, 'public_group_id');
    }

    public function positions(): HasMany
    {
        return $this->hasMany(PublicPosition::class, 'public_subgroup_id')->orderBy('sort_order');
    }
}
