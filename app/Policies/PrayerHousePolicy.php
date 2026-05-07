<?php

namespace App\Policies;

use App\Models\PrayerHouse;
use App\Models\User;

class PrayerHousePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->can('gerenciar_casas_oracao');
    }

    public function view(User $user, PrayerHouse $prayerHouse): bool
    {
        return $this->viewAny($user) && $user->canAccessPrayerHouse($prayerHouse);
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->can('gerenciar_casas_oracao');
    }

    public function update(User $user, PrayerHouse $prayerHouse): bool
    {
        return $this->create($user) && $user->canAccessPrayerHouse($prayerHouse);
    }

    public function delete(User $user, PrayerHouse $prayerHouse): bool
    {
        return $this->update($user, $prayerHouse);
    }
}
