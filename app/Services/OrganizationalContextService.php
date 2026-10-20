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
        $sessionId = session('nexus.active_regional_id');
        if ($sessionId !== null && $sessionId !== '') {
            $id = (int) $sessionId;
            if ($this->isUsableRegional($id)) {
                return $id;
            }
            // Sessão apontava para regional inativa/inexistente (ex.: demo desativada).
            $this->forgetSessionContext();
        }

        $userId = auth()->user()?->regional_id;
        if ($userId && $this->isUsableRegional((int) $userId)) {
            return (int) $userId;
        }

        // Super-admin sem contexto: usa a única regional ativa se houver só uma.
        $active = Regional::query()->where('is_active', true)->orderBy('id')->get(['id']);
        if ($active->count() === 1) {
            return (int) $active->first()->id;
        }

        return null;
    }

    public function activeAdministrationId(): ?int
    {
        $regionalId = $this->activeRegionalId();
        $key = session('nexus.active_administration_id');
        if ($key !== null && $key !== '') {
            $id = (int) $key;
            if ($this->administrationBelongsToRegional($id, $regionalId)) {
                return $id;
            }
            session()->forget('nexus.active_administration_id');
            session()->forget('nexus.active_prayer_house_id');
        }

        $userAdminId = auth()->user()?->administration_id;
        if ($userAdminId && $this->administrationBelongsToRegional((int) $userAdminId, $regionalId)) {
            return (int) $userAdminId;
        }

        return null;
    }

    public function activePrayerHouseId(): ?int
    {
        $administrationId = $this->activeAdministrationId();
        $key = session('nexus.active_prayer_house_id');
        if ($key !== null && $key !== '') {
            $id = (int) $key;
            if ($this->prayerHouseBelongsToAdministration($id, $administrationId)) {
                return $id;
            }
            session()->forget('nexus.active_prayer_house_id');
        }

        $userHouseId = auth()->user()?->prayer_house_id;
        if ($userHouseId && $this->prayerHouseBelongsToAdministration((int) $userHouseId, $administrationId)) {
            return (int) $userHouseId;
        }

        return null;
    }

    public function setActive(?int $regionalId, ?int $administrationId, ?int $prayerHouseId): void
    {
        if ($regionalId !== null && ! $this->isUsableRegional($regionalId)) {
            $regionalId = null;
        }

        if ($administrationId !== null && ! $this->administrationBelongsToRegional($administrationId, $regionalId)) {
            $administrationId = null;
            $prayerHouseId = null;
        }

        if ($prayerHouseId !== null && ! $this->prayerHouseBelongsToAdministration($prayerHouseId, $administrationId)) {
            $prayerHouseId = null;
        }

        session([
            'nexus.active_regional_id' => $regionalId,
            'nexus.active_administration_id' => $administrationId,
            'nexus.active_prayer_house_id' => $prayerHouseId,
        ]);
    }

    public function forgetSessionContext(): void
    {
        session()->forget([
            'nexus.active_regional_id',
            'nexus.active_administration_id',
            'nexus.active_prayer_house_id',
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
            if (! $regional || ! $regional->is_active) {
                return false;
            }

            return $user->canAccessRegional($regional);
        }

        return true;
    }

    protected function isUsableRegional(int $id): bool
    {
        return Regional::query()->whereKey($id)->where('is_active', true)->exists();
    }

    protected function administrationBelongsToRegional(int $administrationId, ?int $regionalId): bool
    {
        if (! $regionalId) {
            return false;
        }

        return Administration::query()
            ->whereKey($administrationId)
            ->where('regional_id', $regionalId)
            ->where('is_active', true)
            ->exists();
    }

    protected function prayerHouseBelongsToAdministration(int $prayerHouseId, ?int $administrationId): bool
    {
        if (! $administrationId) {
            return false;
        }

        return PrayerHouse::query()
            ->whereKey($prayerHouseId)
            ->where('administration_id', $administrationId)
            ->where('is_active', true)
            ->exists();
    }
}
