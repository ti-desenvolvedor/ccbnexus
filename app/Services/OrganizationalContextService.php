<?php

namespace App\Services;

use App\Models\Administration;
use App\Models\PrayerHouse;
use App\Models\Regional;
use App\Models\User;

class OrganizationalContextService
{
    public function activeRegionalId(): ?int
    {
        $key = session('nexus.active_regional_id');
        if ($key !== null && $key !== '') {
            return (int) $key;
        }

        return auth()->user()?->regional_id;
    }

    public function activeAdministrationId(): ?int
    {
        $key = session('nexus.active_administration_id');
        if ($key !== null && $key !== '') {
            return (int) $key;
        }

        return auth()->user()?->administration_id;
    }

    public function activePrayerHouseId(): ?int
    {
        $key = session('nexus.active_prayer_house_id');
        if ($key !== null && $key !== '') {
            return (int) $key;
        }

        return auth()->user()?->prayer_house_id;
    }

    public function setActive(?int $regionalId, ?int $administrationId, ?int $prayerHouseId): void
    {
        session([
            'nexus.active_regional_id' => $regionalId,
            'nexus.active_administration_id' => $administrationId,
            'nexus.active_prayer_house_id' => $prayerHouseId,
        ]);
    }

    public function userMayUseContext(User $user, ?int $regionalId, ?int $administrationId, ?int $prayerHouseId): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($prayerHouseId) {
            $house = PrayerHouse::query()->find($prayerHouseId);
            if (! $house) {
                return false;
            }

            return $user->canAccessPrayerHouse($house);
        }

        if ($administrationId) {
            $admin = Administration::query()->find($administrationId);
            if (! $admin) {
                return false;
            }

            return $user->canAccessAdministration($admin);
        }

        if ($regionalId) {
            $regional = Regional::query()->find($regionalId);
            if (! $regional) {
                return false;
            }

            return $user->canAccessRegional($regional);
        }

        return true;
    }
}
