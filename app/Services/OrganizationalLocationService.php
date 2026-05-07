<?php

namespace App\Services;

use App\Models\Administration;
use App\Models\Event;
use App\Models\Location;
use App\Models\MeetingRoom;
use App\Models\Parking;
use App\Models\PrayerHouse;
use App\Models\Regional;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Garante que "locais" (tabela locations) só aparecem no contexto organizacional correto:
 * endereço de uma casa de oração não deve surgir ao editar outra administração, etc.
 */
class OrganizationalLocationService
{
    /**
     * @return list<int>
     */
    public function referencedLocationIds(): array
    {
        return collect()
            ->merge(Regional::query()->whereNotNull('location_id')->pluck('location_id'))
            ->merge(Administration::query()->whereNotNull('location_id')->pluck('location_id'))
            ->merge(PrayerHouse::query()->whereNotNull('location_id')->pluck('location_id'))
            ->merge(MeetingRoom::query()->whereNotNull('location_id')->pluck('location_id'))
            ->merge(Event::query()->whereNotNull('location_id')->pluck('location_id'))
            ->merge(Parking::query()->pluck('location_id'))
            ->unique()
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Locais ainda não ligados a regional, administração, casa, sala, evento ou estacionamento.
     *
     * @return list<int>
     */
    public function orphanLocationIds(): array
    {
        $referenced = $this->referencedLocationIds();
        if ($referenced === []) {
            return Location::query()->pluck('id')->all();
        }

        return Location::query()->whereNotIn('id', $referenced)->pluck('id')->all();
    }

    /**
     * Ao criar uma nova administração: sede regional + locais ainda sem vínculo (novo endereço).
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

        return $ids->merge($this->orphanLocationIds())->unique()->values()->all();
    }

    /**
     * Locais que uma administração pode usar: sede regional + endereço da própria administração
     * + endereços das casas de oração desta administração (não de outras).
     *
     * @return list<int>
     */
    public function selectableIdsForAdministration(int $administrationId): array
    {
        $admin = Administration::query()->find($administrationId);
        if (! $admin) {
            return [];
        }

        $ids = collect();
        $regional = Regional::query()->find($admin->regional_id);
        if ($regional?->location_id) {
            $ids->push((int) $regional->location_id);
        }
        if ($admin->location_id) {
            $ids->push((int) $admin->location_id);
        }
        $ids = $ids->merge(
            PrayerHouse::query()
                ->where('administration_id', $administrationId)
                ->whereNotNull('location_id')
                ->pluck('location_id')
        );

        return $ids->unique()->filter()->values()->all();
    }

    /**
     * Casas de oração partilham o mesmo conjunto que a administração-mãe (regional + admin + casas da mesma admin).
     *
     * @return list<int>
     */
    public function selectableIdsForPrayerHouse(int $prayerHouseId): array
    {
        $house = PrayerHouse::query()->find($prayerHouseId);

        return $house
            ? $this->selectableIdsForAdministration((int) $house->administration_id)
            : [];
    }

    /**
     * Tudo o que está ligado à árvore da regional (eventos, salas, etc.).
     *
     * @return list<int>
     */
    public function selectableIdsForRegional(int $regionalId): array
    {
        $ids = collect();
        $regional = Regional::query()->find($regionalId);
        if ($regional?->location_id) {
            $ids->push((int) $regional->location_id);
        }

        $adminIds = Administration::query()->where('regional_id', $regionalId)->pluck('id');

        $ids = $ids->merge(
            Administration::query()
                ->where('regional_id', $regionalId)
                ->whereNotNull('location_id')
                ->pluck('location_id')
        );

        $ids = $ids->merge(
            PrayerHouse::query()
                ->whereIn('administration_id', $adminIds)
                ->whereNotNull('location_id')
                ->pluck('location_id')
        );

        $ids = $ids->merge(
            MeetingRoom::query()
                ->where(function ($q) use ($regionalId) {
                    $q->whereHasMorph('owner', [Administration::class], function ($a) use ($regionalId) {
                        $a->where('regional_id', $regionalId);
                    })->orWhereHasMorph('owner', [PrayerHouse::class], function ($ph) use ($regionalId) {
                        $ph->whereHas('administration', function ($a) use ($regionalId) {
                            $a->where('regional_id', $regionalId);
                        });
                    });
                })
                ->whereNotNull('location_id')
                ->pluck('location_id')
        );

        $ids = $ids->merge(
            Event::query()
                ->where('regional_id', $regionalId)
                ->whereNotNull('location_id')
                ->pluck('location_id')
        );

        return $ids->unique()->filter()->values()->all();
    }

    /**
     * @return list<int>
     */
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
     * Locais que o utilizador pode ver na lista global de "Locais" e em estacionamentos.
     * null = sem filtro (super-admin).
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

    /**
     * @param  list<int>|null  $ids  null = todos
     */
    public function orderedLocationsQuery(?array $ids): Builder
    {
        $q = Location::query()->orderBy('name');
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
}
