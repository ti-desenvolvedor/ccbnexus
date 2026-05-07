<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MeetingRoom extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'owner_type',
        'owner_id',
        'location_id',
        'name',
        'slug',
        'capacity',
        'is_active',
        'meta',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(RoomAssignment::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(RoomReservation::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['owner_type', 'owner_id', 'location_id', 'name', 'slug', 'capacity', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function scopeVisibleToUser(Builder $query, User $user): void
    {
        if ($user->isSuperAdmin()) {
            return;
        }

        $regionalIds = $user->accessibleRegionalIds();
        if ($regionalIds === []) {
            $query->whereRaw('0 = 1');

            return;
        }

        $query->where(function (Builder $outer) use ($regionalIds) {
            $outer->whereHasMorph('owner', [Administration::class], function (Builder $admin) use ($regionalIds) {
                $admin->whereIn('regional_id', $regionalIds);
            })->orWhereHasMorph('owner', [PrayerHouse::class], function (Builder $house) use ($regionalIds) {
                $house->whereHas('administration', function (Builder $admin) use ($regionalIds) {
                    $admin->whereIn('regional_id', $regionalIds);
                });
            })->orWhereHas('assignments', function (Builder $assignments) use ($regionalIds) {
                $assignments->where(function (Builder $a) use ($regionalIds) {
                    $a->whereHasMorph('assignable', [Regional::class], function (Builder $r) use ($regionalIds) {
                        $r->whereIn('id', $regionalIds);
                    })->orWhereHasMorph('assignable', [Administration::class], function (Builder $adm) use ($regionalIds) {
                        $adm->whereIn('regional_id', $regionalIds);
                    })->orWhereHasMorph('assignable', [PrayerHouse::class], function (Builder $ph) use ($regionalIds) {
                        $ph->whereHas('administration', function (Builder $adm) use ($regionalIds) {
                            $adm->whereIn('regional_id', $regionalIds);
                        });
                    });
                });
            });
        });
    }
}
