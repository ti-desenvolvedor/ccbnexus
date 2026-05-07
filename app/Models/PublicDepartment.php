<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PublicDepartment extends Model
{
    public const SCOPE_REGIONAL = 'regional';

    public const SCOPE_ADMINISTRATION = 'administration';

    public const SCOPE_PRAYER_HOUSE = 'prayer_house';

    protected $table = 'public_departments';

    protected $fillable = [
        'scope',
        'regional_id',
        'administration_id',
        'prayer_house_id',
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

    public function administration(): BelongsTo
    {
        return $this->belongsTo(Administration::class);
    }

    public function prayerHouse(): BelongsTo
    {
        return $this->belongsTo(PrayerHouse::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(PublicPosition::class, 'public_department_id')->orderBy('sort_order');
    }

    /**
     * Departamentos cuja âncora organizacional pertence à regional indicada.
     */
    public function scopeForRegionalContext(Builder $query, int $regionalId): void
    {
        $query->where(function (Builder $q) use ($regionalId) {
            $q->where(function (Builder $q2) use ($regionalId) {
                $q2->where('scope', self::SCOPE_REGIONAL)->where('regional_id', $regionalId);
            })->orWhere(function (Builder $q2) use ($regionalId) {
                $q2->where('scope', self::SCOPE_ADMINISTRATION)
                    ->whereHas('administration', fn (Builder $a) => $a->where('regional_id', $regionalId));
            })->orWhere(function (Builder $q2) use ($regionalId) {
                $q2->where('scope', self::SCOPE_PRAYER_HOUSE)
                    ->whereHas('prayerHouse.administration', fn (Builder $a) => $a->where('regional_id', $regionalId));
            });
        });
    }

    /**
     * Regional de contexto para exibição (denormalização lógica).
     */
    public function contextRegionalId(): ?int
    {
        return match ($this->scope) {
            self::SCOPE_REGIONAL => $this->regional_id,
            self::SCOPE_ADMINISTRATION => $this->administration?->regional_id,
            self::SCOPE_PRAYER_HOUSE => $this->prayerHouse?->administration?->regional_id,
            default => null,
        };
    }
}
