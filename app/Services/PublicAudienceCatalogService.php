<?php

namespace App\Services;

use App\Models\PublicDepartment;
use App\Models\PublicGroup;
use App\Models\PublicPosition;
use App\Models\PublicSubgroup;
use App\Models\Regional;
use App\Models\User;
use App\Services\PublicAudience\PublicCatalogOrganogramSeeder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PublicAudienceCatalogService
{
    /**
     * @return array{groups: list<array<string, mixed>>, statutory_positions: array<string, list<array<string, mixed>>>, department_positions: array<string, list<array<string, mixed>>>}
     */
    public function organogramDefinition(): array
    {
        return require database_path('seeders/data/public_catalog_organogram.php');
    }

    public function allowedPositionIdsForRegional(User $user, int $regionalId, ?string $departmentScope = null): array
    {
        if (! $user->isSuperAdmin() && ! $user->canAccessRegional(Regional::query()->findOrFail($regionalId))) {
            abort(403);
        }

        $query = PublicPosition::query()
            ->active()
            ->forRegionalContext($regionalId);

        if ($departmentScope !== null) {
            $query->whereHas('department', fn (Builder $d) => $d->where('scope', $departmentScope));
        }

        return $query->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * @return array{
     *     administrative: Collection,
     *     operational: Collection,
     *     ministry: Collection,
     *     music: Collection
     * }
     */
    public function audiencePickerForEvent(
        User $user,
        ?int $regionalId,
        string $departmentScope = PublicDepartment::SCOPE_REGIONAL,
        ?int $administrationId = null,
    ): array {
        if (! $regionalId) {
            return [
                'administrative' => collect(),
                'operational' => collect(),
                'ministry' => collect(),
                'music' => collect(),
            ];
        }

        if (! $user->isSuperAdmin() && ! $user->canAccessRegional(Regional::query()->findOrFail($regionalId))) {
            abort(403);
        }

        $administrationId = $departmentScope === PublicDepartment::SCOPE_ADMINISTRATION
            ? $administrationId
            : null;

        return [
            'administrative' => $this->administrativePickerBlocks(
                $user,
                (int) $regionalId,
                $departmentScope,
                $administrationId,
            ),
            'operational' => $this->axisPickerRows(
                $user,
                (int) $regionalId,
                'operational',
                $departmentScope,
                $administrationId,
            ),
            'ministry' => $this->axisPickerRows(
                $user,
                (int) $regionalId,
                'ministry',
                $departmentScope,
                $administrationId,
            ),
            'music' => $this->musicPickerForEvent(
                $user,
                (int) $regionalId,
                $departmentScope,
                $administrationId,
            ),
        ];
    }

    /**
     * @return list<int>
     */
    public function positionIdsForSubgroup(int $regionalId, int $subgroupId, string $departmentScope): array
    {
        return PublicPosition::query()
            ->active()
            ->forRegionalContext($regionalId)
            ->whereHas('department', fn (Builder $d) => $d
                ->where('scope', $departmentScope)
                ->where('public_subgroup_id', $subgroupId))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @return list<int>
     */
    public function positionIdsForDepartment(int $departmentId): array
    {
        return PublicPosition::query()
            ->active()
            ->where('public_department_id', $departmentId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function departmentsQueryForUser(User $user): Builder
    {
        $query = PublicDepartment::query()
            ->with(['regional', 'administration', 'prayerHouse', 'subgroup.group'])
            ->withCount('positions');

        if (! $user->isSuperAdmin()) {
            $ids = $user->accessibleRegionalIds();
            if ($ids === []) {
                return $query->whereRaw('0 = 1');
            }
            $query->where(function (Builder $outer) use ($ids) {
                foreach ($ids as $rid) {
                    $outer->orWhere(fn (Builder $q) => $q->forRegionalContext((int) $rid));
                }
            });
        }

        return $query;
    }

    public function departmentsForUser(User $user): Collection
    {
        return $this->departmentsQueryForUser($user)
            ->orderBy('scope')
            ->orderBy('name')
            ->get();
    }

    public function groupForRegional(int $regionalId, string $groupSlug): ?PublicGroup
    {
        return PublicGroup::query()
            ->where('regional_id', $regionalId)
            ->where('slug', $groupSlug)
            ->first();
    }

    public function administrativeGroupForRegional(int $regionalId): ?PublicGroup
    {
        return $this->groupForRegional($regionalId, PublicCatalogOrganogramSeeder::ADMIN_GROUP_SLUG);
    }

    public function operationalGroupForRegional(int $regionalId): ?PublicGroup
    {
        return $this->groupForRegional($regionalId, PublicCatalogOrganogramSeeder::OPERATIONAL_GROUP_SLUG);
    }

    public function ministryGroupForRegional(int $regionalId): ?PublicGroup
    {
        return $this->groupForRegional($regionalId, PublicCatalogOrganogramSeeder::MINISTRY_GROUP_SLUG);
    }

    /**
     * Cargos do departamento Ministério já cadastrados na regional.
     * Um cargo por slug, preferindo o âmbito regional.
     */
    public function ministryOffices(int $regionalId): Collection
    {
        return PublicPosition::query()
            ->active()
            ->forRegionalContext($regionalId)
            ->with('department')
            ->whereHas('department', fn (Builder $department) => $department->where('slug', 'ministerio'))
            ->get()
            ->sortBy(fn (PublicPosition $position) => sprintf(
                '%d-%05d-%d',
                $position->department?->scope === PublicDepartment::SCOPE_REGIONAL ? 0 : 1,
                $position->sort_order,
                $position->id,
            ))
            ->unique('slug')
            ->values();
    }

    public function musicGroupForRegional(int $regionalId): ?PublicGroup
    {
        return $this->groupForRegional($regionalId, PublicCatalogOrganogramSeeder::MUSIC_GROUP_SLUG);
    }

    /**
     * @return Collection<int, PublicSubgroup>
     */
    public function administrativeSubgroupsForRegional(int $regionalId): Collection
    {
        $group = $this->administrativeGroupForRegional($regionalId);
        if (! $group) {
            return collect();
        }

        return $group->subgroups()->with('group')->where('slug', 'like', 'grupo-%')->orderBy('sort_order')->get();
    }

    public function administrativeStructureForRegional(
        User $user,
        int $regionalId,
        string $scope,
        ?int $administrationId = null,
    ): Collection {
        if (! $user->isSuperAdmin() && ! $user->canAccessRegional(Regional::query()->findOrFail($regionalId))) {
            abort(403);
        }

        return $this->administrativeSubgroupsForRegional($regionalId)->map(function (PublicSubgroup $subgroup) use ($regionalId, $scope, $administrationId) {
            $deptQuery = PublicDepartment::query()
                ->where('public_subgroup_id', $subgroup->id)
                ->where(fn (Builder $q) => $q->where('meta->axis', 'administrative')->orWhereNull('meta->axis'))
                ->withCount('positions');

            if ($scope === PublicDepartment::SCOPE_REGIONAL) {
                $deptQuery->where('scope', PublicDepartment::SCOPE_REGIONAL)->where('regional_id', $regionalId);
            } else {
                $deptQuery->where('scope', PublicDepartment::SCOPE_ADMINISTRATION)
                    ->when($administrationId, fn (Builder $q) => $q->where('administration_id', $administrationId));
            }

            $departments = $deptQuery->orderBy('sort_order')->get();

            $rows = $departments->map(function (PublicDepartment $department) use ($regionalId, $administrationId) {
                return [
                    'department' => $department,
                    'positions_count' => (int) $department->positions_count,
                    'has_regional_pair' => PublicDepartment::query()
                        ->where('slug', $department->slug)
                        ->where('scope', PublicDepartment::SCOPE_REGIONAL)
                        ->where('regional_id', $regionalId)
                        ->exists(),
                    'has_admin_pair' => $administrationId
                        ? PublicDepartment::query()
                            ->where('slug', $department->slug)
                            ->where('scope', PublicDepartment::SCOPE_ADMINISTRATION)
                            ->where('administration_id', $administrationId)
                            ->exists()
                        : false,
                ];
            });

            return ['subgroup' => $subgroup, 'departments' => $rows];
        });
    }

    public function operationalStructureForRegional(
        User $user,
        int $regionalId,
        string $scope,
        ?int $administrationId = null,
        ?int $prayerHouseId = null,
    ): Collection {
        return $this->axisStructureForRegional($user, $regionalId, 'operational', $scope, $administrationId, $prayerHouseId);
    }

    public function ministryStructureForRegional(
        User $user,
        int $regionalId,
        string $scope,
        ?int $administrationId = null,
    ): Collection {
        return $this->axisStructureForRegional($user, $regionalId, 'ministry', $scope, $administrationId);
    }

    public function musicStructureForRegional(
        User $user,
        int $regionalId,
        string $scope,
        ?int $administrationId = null,
        ?int $prayerHouseId = null,
    ): Collection {
        return $this->axisStructureForRegional($user, $regionalId, 'music', $scope, $administrationId, $prayerHouseId);
    }

    /**
     * @return Collection<int, array{department: PublicDepartment, positions: Collection<int, PublicPosition>}>
     */
    public function axisStructureForRegional(
        User $user,
        int $regionalId,
        string $axis,
        string $scope,
        ?int $administrationId = null,
        ?int $prayerHouseId = null,
    ): Collection {
        if (! $user->isSuperAdmin() && ! $user->canAccessRegional(Regional::query()->findOrFail($regionalId))) {
            abort(403);
        }

        $query = PublicDepartment::query()
            ->with(['positions' => fn ($q) => $q->active()->orderBy('sort_order'), 'administration', 'prayerHouse'])
            ->where('meta->axis', $axis)
            ->forRegionalContext($regionalId);

        $this->applyScopeFilter($query, $scope, $regionalId, $administrationId, $prayerHouseId);

        $order = collect($this->operationalSortOrder($axis));

        return $query->get()
            ->sortBy(fn (PublicDepartment $d) => $order->get($d->slug, 999))
            ->values()
            ->map(fn (PublicDepartment $department) => [
                'department' => $department,
                'positions' => $department->positions,
            ]);
    }

    public function subgroupsForUser(User $user): Collection
    {
        $query = PublicSubgroup::query()->with('group')->orderBy('sort_order');

        if ($user->isSuperAdmin()) {
            return $query->get();
        }

        $ids = $user->accessibleRegionalIds();
        if ($ids === []) {
            return collect();
        }

        return $query->whereHas('group', fn (Builder $g) => $g->whereIn('regional_id', $ids))->get();
    }

    /**
     * @return Collection<int, array{subgroup: PublicSubgroup, departments: Collection}>
     */
    private function administrativePickerBlocks(
        User $user,
        int $regionalId,
        string $scope,
        ?int $administrationId,
    ): Collection {
        return $this->administrativeStructureForRegional($user, $regionalId, $scope, $administrationId)
            ->map(function (array $block) {
                $departmentIds = $block['departments']->pluck('department.id')->all();
                $positionsByDept = PublicPosition::query()
                    ->active()
                    ->whereIn('public_department_id', $departmentIds)
                    ->orderBy('sort_order')
                    ->get()
                    ->groupBy('public_department_id');

                $departments = $block['departments']->map(fn (array $row) => [
                    'department' => $row['department'],
                    'positions' => $positionsByDept->get($row['department']->id, collect()),
                ]);

                return ['subgroup' => $block['subgroup'], 'departments' => $departments];
            });
    }

    /**
     * @return Collection<int, array{department: PublicDepartment, positions: Collection}>
     */
    private function axisPickerRows(
        User $user,
        int $regionalId,
        string $axis,
        string $scope,
        ?int $administrationId,
    ): Collection {
        return $this->axisStructureForRegional($user, $regionalId, $axis, $scope, $administrationId);
    }

    /**
     * Música: em âmbito regional agrega encarregados de todas as administrações.
     *
     * @return Collection<int, array{department?: PublicDepartment, positions: Collection, label?: string, administration_name?: string}>
     */
    private function musicPickerForEvent(
        User $user,
        int $regionalId,
        string $scope,
        ?int $administrationId,
    ): Collection {
        if ($scope === PublicDepartment::SCOPE_REGIONAL) {
            $rows = collect();

            $regionalRows = $this->axisStructureForRegional($user, $regionalId, 'music', PublicDepartment::SCOPE_REGIONAL);
            foreach ($regionalRows as $row) {
                $rows->push($row);
            }

            $adminDepts = PublicDepartment::query()
                ->with(['positions' => fn ($q) => $q->active()->orderBy('sort_order'), 'administration'])
                ->where('slug', 'musica')
                ->where('scope', PublicDepartment::SCOPE_ADMINISTRATION)
                ->where('meta->axis', 'music')
                ->whereHas('administration', fn (Builder $q) => $q->where('regional_id', $regionalId))
                ->orderBy('sort_order')
                ->get();

            foreach ($adminDepts as $dept) {
                if ($dept->positions->isEmpty()) {
                    continue;
                }
                $rows->push([
                    'department' => $dept,
                    'positions' => $dept->positions,
                    'label' => $dept->administration?->name,
                ]);
            }

            return $rows;
        }

        return $this->axisStructureForRegional($user, $regionalId, 'music', $scope, $administrationId);
    }

    private function applyScopeFilter(
        Builder $query,
        string $scope,
        int $regionalId,
        ?int $administrationId,
        ?int $prayerHouseId,
    ): void {
        if ($scope === PublicDepartment::SCOPE_REGIONAL) {
            $query->where('scope', PublicDepartment::SCOPE_REGIONAL)->where('regional_id', $regionalId);
        } elseif ($scope === PublicDepartment::SCOPE_ADMINISTRATION) {
            $query->where('scope', PublicDepartment::SCOPE_ADMINISTRATION);
            if ($administrationId) {
                $query->where('administration_id', $administrationId);
            }
        } else {
            $query->where('scope', PublicDepartment::SCOPE_PRAYER_HOUSE);
            if ($prayerHouseId) {
                $query->where('prayer_house_id', $prayerHouseId);
            }
        }
    }

    /**
     * @return array<string, int>
     */
    private function operationalSortOrder(string $axis): array
    {
        $organogram = $this->organogramDefinition();
        foreach ($organogram['groups'] as $group) {
            if (($group['axis'] ?? '') !== $axis) {
                continue;
            }
            foreach ($group['subgroups'] as $sg) {
                $order = [];
                foreach ($sg['departments'] ?? [] as $dept) {
                    $order[$dept['slug']] = $dept['sort_order'] ?? 10;
                }

                return $order;
            }
        }

        return [];
    }
}
