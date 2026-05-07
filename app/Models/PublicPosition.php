<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PublicPosition extends Model
{
    protected $table = 'public_positions';

    protected $fillable = [
        'public_department_id',
        'public_subgroup_id',
        'name',
        'slug',
        'is_active',
        'sort_order',
        'is_department_coordinator',
        'meta',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_department_coordinator' => 'boolean',
        'meta' => 'array',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(PublicDepartment::class, 'public_department_id');
    }

    public function subgroup(): BelongsTo
    {
        return $this->belongsTo(PublicSubgroup::class, 'public_subgroup_id');
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_public_position', 'public_position_id', 'event_id')
            ->withTimestamps();
    }

    public function scopeForRegionalContext(Builder $query, int $regionalId): void
    {
        $query->whereHas('department', fn (Builder $d) => $d->forRegionalContext($regionalId));
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Rótulo hierárquico para listas (evento, exportações).
     */
    public function labelForEventPicker(): string
    {
        $parts = [];
        if ($this->relationLoaded('subgroup') && $this->subgroup) {
            $this->subgroup->loadMissing('group');
            if ($this->subgroup->group) {
                $parts[] = $this->subgroup->group->name;
            }
            $parts[] = $this->subgroup->name;
        }
        if ($this->relationLoaded('department') && $this->department) {
            $parts[] = $this->department->name;
        }
        $parts[] = $this->name;
        $label = implode(' › ', array_filter($parts));
        if ($this->is_department_coordinator) {
            $label .= ' ('.__('Coord. departamento').')';
        }

        return $label;
    }
}
