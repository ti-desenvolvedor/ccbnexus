<?php

namespace App\Services;

use App\Models\Administration;
use App\Models\Location;
use App\Models\PrayerHouse;
use App\Models\Regional;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

/**
 * Escopo de locais (tabela locations) por administração/regional.
 */
class OrganizationalLocationService
{
    /**
     * Locais ainda sem dono explícito (criação de regional/administração).
     *
     * @return list<int>
     */
    public function selectableIdsForNewRegional(): array
    {
        return Location::query()
            ->whereNull('regional_id')
            ->whereNull('administration_id')
            ->pluck('id')
            ->all();
    }

    /**
     * Catálogo de locais de uma administração (sem sede regional no dropdown de casa/sala).
     *
     * @return list<int>
     */
    public function selectableIdsForAdministration(int $administrationId): array
    {
        if ($administrationId <= 0) {
            return [];
        }

        return Location::query()
            ->where('administration_id', $administrationId)
            ->pluck('id')
            ->all();
    }

    /**
     * Pool ao criar nova administração: sede regional + locais regionais sem administração.
     *
     * @return list<int>
     */
    public function selectableIdsForNewAdministration(int $regionalId): array
    {
        $ids = collect();

        $regional = Regional::query()->find($regionalId);
        if ($regional?->location_id) {
            $ids->push((int) $regional->location_id);
        }

        $ids = $ids->merge(
            Location::query()
                ->where('regional_id', $regionalId)
                ->whereNull('administration_id')
                ->pluck('id')
        );

        return $ids->unique()->filter()->values()->all();
    }

    /**
     * Todos os locais visíveis no âmbito de uma regional (eventos, listagem ampla).
     *
     * @return list<int>
     */
    public function selectableIdsForRegional(int $regionalId): array
    {
        if ($regionalId <= 0) {
            return [];
        }

        $adminIds = Administration::query()->where('regional_id', $regionalId)->pluck('id');

        return Location::query()
            ->where(function (Builder $q) use ($regionalId, $adminIds) {
                $q->where('regional_id', $regionalId)
                    ->orWhereIn('administration_id', $adminIds);
            })
            ->pluck('id')
            ->all();
    }

    public function selectableIdsForPrayerHouse(int $prayerHouseId): array
    {
        $house = PrayerHouse::query()->find($prayerHouseId);

        return $house
          ? $this->selectableIdsForAdministration((int) $house->administration_id)
          : [];
    }

    public function selectableIdsForMeetingRoomOwner(string $ownerType, int $ownerId): array
    {
        if ($ownerType === Administration::class) {
            return $this->selectableIdsForAdministration($ownerId);
        }
        if ($ownerType === PrayerHouse::class) {
            return $this->selectableIdsForPrayerHouse($ownerId);
        }

        return [];
    }

    /**
     * IDs de locais que o utilizador pode ver (null = super-admin sem filtro na listagem global).
     *
     * @return list<int>|null
     */
    public function visibleLocationIdsForUser(User $user): ?array
    {
        if ($user->isSuperAdmin()) {
            return null;
        }

        $ids = collect();
        foreach ($user->accessibleRegionalIds() as $rid) {
            $ids = $ids->merge($this->selectableIdsForRegional((int) $rid));
        }

        return $ids->unique()->filter()->values()->all();
    }

    public function userMayAccessLocation(User $user, Location $location): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($location->administration_id) {
            $admin = $location->administration;
            if ($admin && $user->canAccessAdministration($admin)) {
                return true;
            }
        }

        if ($location->regional_id) {
            return in_array((int) $location->regional_id, $user->accessibleRegionalIds(), true);
        }

        $visible = $this->visibleLocationIdsForUser($user);

        return is_array($visible) && in_array($location->id, $visible, true);
    }

    /**
     * @param  list<int>|null  $ids
     */
    public function orderedLocationsQuery(?array $ids): Builder
    {
        $q = Location::query()->with(['administration.regional', 'regional'])->orderBy('name');
        if ($ids !== null) {
            if ($ids === []) {
                $q->whereRaw('0 = 1');
            } else {
                $q->whereIn('id', $ids);
            }
        }

        return $q;
    }

    /**
     * @param  list<int>  $allowed
     * @return list<int>
     */
    public function mergeCurrentLocation(array $allowed, ?int $currentId): array
    {
        if ($currentId === null || $currentId === 0) {
            return $allowed;
        }

        return collect($allowed)->push($currentId)->unique()->values()->all();
    }

    public function assertLocationAllowed(?int $locationId, array $allowedIds, string $field = 'location_id'): void
    {
        if ($locationId === null || $locationId === 0) {
            return;
        }

        if (! in_array($locationId, $allowedIds, true)) {
            throw ValidationException::withMessages([
                $field => [__('Este local não pertence ao âmbito selecionado.')],
            ]);
        }
    }
}
