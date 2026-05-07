<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use Notifiable;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'phone',
        'google_id',
        'regional_id',
        'administration_id',
        'prayer_house_id',
        'is_super_admin',
    ];

    /**
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_super_admin' => 'boolean',
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

    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }

    /**
     * @return list<int>
     */
    public function accessibleRegionalIds(): array
    {
        if ($this->isSuperAdmin()) {
            return Regional::query()->pluck('id')->all();
        }

        $ids = collect();
        if ($this->regional_id) {
            $ids->push((int) $this->regional_id);
        }
        $this->loadMissing(['administration', 'prayerHouse.administration']);
        if ($this->administration) {
            $ids->push((int) $this->administration->regional_id);
        }
        if ($this->prayerHouse?->administration) {
            $ids->push((int) $this->prayerHouse->administration->regional_id);
        }

        return $ids->unique()->values()->all();
    }

    public function canAccessRegional(Regional $regional): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->regional_id === $regional->id) {
            return true;
        }

        if ($this->administration_id && $this->administration?->regional_id === $regional->id) {
            return true;
        }

        if ($this->prayer_house_id && $this->prayerHouse?->administration?->regional_id === $regional->id) {
            return true;
        }

        return false;
    }

    public function canAccessAdministration(Administration $administration): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->administration_id === $administration->id) {
            return true;
        }

        if ($this->prayer_house_id && $this->prayerHouse?->administration_id === $administration->id) {
            return true;
        }

        if ($this->regional_id === $administration->regional_id) {
            return true;
        }

        return false;
    }

    public function canAccessPrayerHouse(PrayerHouse $prayerHouse): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->prayer_house_id === $prayerHouse->id) {
            return true;
        }

        if ($this->administration_id === $prayerHouse->administration_id) {
            return true;
        }

        return $this->regional_id === $prayerHouse->administration?->regional_id;
    }

    /**
     * Regional operacional derivada do vínculo direto (regional) ou indireto (administração / casa).
     */
    public function organizationalRegionalId(): ?int
    {
        $this->loadMissing(['administration', 'prayerHouse.administration']);
        if ($this->regional_id) {
            return (int) $this->regional_id;
        }
        if ($this->administration_id && $this->administration) {
            return (int) $this->administration->regional_id;
        }
        if ($this->prayer_house_id && $this->prayerHouse?->administration) {
            return (int) $this->prayerHouse->administration->regional_id;
        }

        return null;
    }

    /**
     * Utilizador com `gerenciar_usuarios` pode gerir outro utilizador na mesma árvore regional (super-admin gere todos).
     */
    public function canManageUser(User $target): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (! $this->can('gerenciar_usuarios')) {
            return false;
        }

        if ($target->is_super_admin) {
            return false;
        }

        $targetRegional = $target->organizationalRegionalId();
        if ($targetRegional === null) {
            return false;
        }

        return in_array($targetRegional, $this->accessibleRegionalIds(), true);
    }

    public function canAccessMeetingRoom(MeetingRoom $room): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $room->loadMissing(['owner', 'assignments']);

        $owner = $room->owner;
        if ($owner instanceof Administration && $this->canAccessAdministration($owner)) {
            return true;
        }

        if ($owner instanceof PrayerHouse && $this->canAccessPrayerHouse($owner)) {
            return true;
        }

        foreach ($room->assignments as $assignment) {
            $assignable = $assignment->assignable;
            if ($assignable instanceof Regional && $this->canAccessRegional($assignable)) {
                return true;
            }
            if ($assignable instanceof Administration && $this->canAccessAdministration($assignable)) {
                return true;
            }
            if ($assignable instanceof PrayerHouse && $this->canAccessPrayerHouse($assignable)) {
                return true;
            }
        }

        return false;
    }
}
