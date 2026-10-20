<?php

namespace App\Services\PublicAudience;

use App\Models\Administration;
use App\Models\PrayerHouse;
use App\Models\PublicDepartment;
use App\Models\PublicGroup;
use App\Models\PublicPosition;
use App\Models\PublicSubgroup;
use App\Models\Regional;
use Illuminate\Support\Facades\DB;

class PublicCatalogOrganogramSeeder
{
    public const ADMIN_GROUP_SLUG = 'colaboradores-administrativos';

    public const OPERATIONAL_GROUP_SLUG = 'servicos-operacionais';

    public const MINISTRY_GROUP_SLUG = 'ministerio';

    public const MUSIC_GROUP_SLUG = 'musica';

    /** @var array<string, PublicSubgroup> */
    private array $subgroupsBySlug = [];

    /** @var array<string, PublicGroup> */
    private array $groupsBySlug = [];

    /**
     * @return array{groups: int, departments: int, positions: int}
     */
    public function seedForRegional(Regional $regional): array
    {
        $organogram = require database_path('seeders/data/public_catalog_organogram.php');

        $departmentCount = 0;
        $positionCount = 0;

        foreach ($organogram['groups'] as $groupDef) {
            $group = PublicGroup::query()->updateOrCreate(
                ['regional_id' => $regional->id, 'slug' => $groupDef['slug']],
                [
                    'name' => $groupDef['name'],
                    'is_active' => true,
                    'sort_order' => $groupDef['sort_order'],
                    'meta' => ['axis' => $groupDef['axis']],
                ],
            );
            $this->groupsBySlug[$groupDef['slug']] = $group;

            foreach ($groupDef['subgroups'] as $sgDef) {
                $subgroup = PublicSubgroup::query()->updateOrCreate(
                    ['public_group_id' => $group->id, 'slug' => $sgDef['slug']],
                    [
                        'name' => $sgDef['name'],
                        'is_active' => true,
                        'sort_order' => $sgDef['sort_order'],
                        'meta' => null,
                    ],
                );
                $this->subgroupsBySlug[$groupDef['slug'].'.'.$sgDef['slug']] = $subgroup;

                foreach ($sgDef['departments'] ?? [] as $deptDef) {
                    $departmentCount += $this->seedDepartmentInstances(
                        $regional,
                        $subgroup,
                        $groupDef['axis'],
                        $deptDef,
                    );
                }
            }
        }

        $estatutariosSubgroup = $this->subgroupsBySlug[self::ADMIN_GROUP_SLUG.'.estatutarios'] ?? null;
        if ($estatutariosSubgroup) {
            $positionCount += $this->seedStatutoryPositions($regional, $estatutariosSubgroup, $organogram['statutory_positions'] ?? []);
        }

        $positionCount += $this->seedDepartmentPositions($regional, $organogram['department_positions'] ?? []);

        return [
            'groups' => count($this->groupsBySlug),
            'departments' => $departmentCount,
            'positions' => $positionCount,
        ];
    }

    /**
     * @param  array{slug: string, name: string, sort_order?: int, number?: int, availability: array<string, bool>}  $deptDef
     */
    private function seedDepartmentInstances(
        Regional $regional,
        PublicSubgroup $subgroup,
        string $axis,
        array $deptDef,
    ): int {
        $count = 0;
        $availability = $deptDef['availability'];
        $meta = array_filter([
            'axis' => $axis,
            'catalog_number' => $deptDef['number'] ?? null,
        ]);

        if ($availability['regional'] ?? false) {
            $this->upsertDepartment(
                PublicDepartment::SCOPE_REGIONAL,
                $deptDef,
                $subgroup,
                $regional->id,
                null,
                null,
                $meta,
            );
            $count++;
        }

        if ($availability['administration'] ?? false) {
            $administrations = Administration::query()
                ->where('regional_id', $regional->id)
                ->orderBy('name')
                ->get();

            foreach ($administrations as $administration) {
                $this->upsertDepartment(
                    PublicDepartment::SCOPE_ADMINISTRATION,
                    $deptDef,
                    $subgroup,
                    null,
                    $administration->id,
                    null,
                    $meta,
                );
                $count++;
            }
        }

        if ($availability['prayer_house'] ?? false) {
            $houses = PrayerHouse::query()
                ->whereHas('administration', fn ($q) => $q->where('regional_id', $regional->id))
                ->orderBy('name')
                ->get();

            foreach ($houses as $house) {
                $this->upsertDepartment(
                    PublicDepartment::SCOPE_PRAYER_HOUSE,
                    $deptDef,
                    $subgroup,
                    null,
                    null,
                    $house->id,
                    $meta,
                );
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function upsertDepartment(
        string $scope,
        array $deptDef,
        PublicSubgroup $subgroup,
        ?int $regionalId,
        ?int $administrationId,
        ?int $prayerHouseId,
        array $meta,
    ): PublicDepartment {
        $keys = ['scope' => $scope, 'slug' => $deptDef['slug']];
        if ($scope === PublicDepartment::SCOPE_REGIONAL) {
            $keys['regional_id'] = $regionalId;
        } elseif ($scope === PublicDepartment::SCOPE_ADMINISTRATION) {
            $keys['administration_id'] = $administrationId;
        } else {
            $keys['prayer_house_id'] = $prayerHouseId;
        }

        return PublicDepartment::query()->updateOrCreate(
            $keys,
            [
                'regional_id' => $regionalId,
                'administration_id' => $administrationId,
                'prayer_house_id' => $prayerHouseId,
                'public_subgroup_id' => $subgroup->id,
                'name' => $deptDef['name'],
                'is_active' => true,
                'sort_order' => $deptDef['sort_order'] ?? 10,
                'meta' => $meta,
            ],
        );
    }

    /**
     * @param  array<string, list<array<string, mixed>>>  $statutory
     */
    private function seedStatutoryPositions(Regional $regional, PublicSubgroup $subgroup, array $statutory): int
    {
        $count = 0;

        foreach ($statutory as $deptSlug => $positions) {
            foreach ([PublicDepartment::SCOPE_REGIONAL, PublicDepartment::SCOPE_ADMINISTRATION] as $scope) {
                $query = PublicDepartment::query()
                    ->where('slug', $deptSlug)
                    ->where('scope', $scope);

                if ($scope === PublicDepartment::SCOPE_REGIONAL) {
                    $query->where('regional_id', $regional->id);
                } else {
                    $query->whereHas('administration', fn ($q) => $q->where('regional_id', $regional->id));
                }

                $departments = $query->get();

                foreach ($departments as $department) {
                    foreach ($positions as $posDef) {
                        $allowedScopes = $posDef['scopes'] ?? ['regional', 'administration'];
                        if (! in_array($scope, $allowedScopes, true)) {
                            continue;
                        }

                        $this->upsertPosition($department, $subgroup, $posDef);
                        $count++;
                    }
                }
            }
        }

        return $count;
    }

    /**
     * @param  array<string, list<array<string, mixed>>>  $byDeptSlug
     */
    private function seedDepartmentPositions(Regional $regional, array $byDeptSlug): int
    {
        $count = 0;

        foreach ($byDeptSlug as $deptSlug => $positions) {
            $departments = PublicDepartment::query()
                ->where('slug', $deptSlug)
                ->forRegionalContext($regional->id)
                ->get();

            foreach ($departments as $department) {
                $subgroup = $department->subgroup;
                if (! $subgroup) {
                    continue;
                }

                foreach ($positions as $posDef) {
                    $allowedScopes = $posDef['scopes'] ?? null;
                    if ($allowedScopes !== null && ! in_array($department->scope, $allowedScopes, true)) {
                        continue;
                    }

                    $posDef = $this->positionWithScopeLabel($posDef, $department);
                    $this->upsertPosition($department, $subgroup, $posDef);
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * @param  array<string, mixed>  $posDef
     * @return array<string, mixed>
     */
    private function positionWithScopeLabel(array $posDef, PublicDepartment $department): array
    {
        if (($posDef['slug'] ?? '') !== 'responsavel') {
            return $posDef;
        }

        $scopeLabel = match ($department->scope) {
            PublicDepartment::SCOPE_REGIONAL => __('regional'),
            PublicDepartment::SCOPE_ADMINISTRATION => __('administração'),
            default => __('local'),
        };

        $posDef['name'] = __('Responsável — :dept (:scope)', [
            'dept' => $department->name,
            'scope' => $scopeLabel,
        ]);

        return $posDef;
    }

    /**
     * @param  array<string, mixed>  $posDef
     */
    private function upsertPosition(
        PublicDepartment $department,
        PublicSubgroup $subgroup,
        array $posDef,
    ): void {
        PublicPosition::query()->updateOrCreate(
            ['public_department_id' => $department->id, 'slug' => $posDef['slug']],
            [
                'public_subgroup_id' => $subgroup->id,
                'name' => $posDef['name'],
                'is_active' => true,
                'sort_order' => $posDef['sort_order'] ?? 10,
                'is_department_coordinator' => (bool) ($posDef['is_coordinator'] ?? false),
                'meta' => null,
            ],
        );
    }

    /**
     * Remove catálogo público de uma regional (rebuild limpo).
     */
    public function purgeForRegional(Regional $regional): array
    {
        $departmentIds = PublicDepartment::query()
            ->forRegionalContext($regional->id)
            ->pluck('id');

        $positionIds = PublicPosition::query()
            ->whereIn('public_department_id', $departmentIds)
            ->pluck('id');

        $pivotDeleted = 0;
        if ($positionIds->isNotEmpty()) {
            $pivotDeleted = DB::table('event_public_position')
                ->whereIn('public_position_id', $positionIds)
                ->delete();
        }

        $positionsDeleted = PublicPosition::query()
            ->whereIn('id', $positionIds)
            ->delete();

        $departmentsDeleted = PublicDepartment::query()
            ->whereIn('id', $departmentIds)
            ->delete();

        $groupSlugs = array_column((require database_path('seeders/data/public_catalog_organogram.php'))['groups'], 'slug');

        $groupIds = PublicGroup::query()
            ->where('regional_id', $regional->id)
            ->whereIn('slug', $groupSlugs)
            ->pluck('id');

        $subgroupsDeleted = PublicSubgroup::query()
            ->whereIn('public_group_id', $groupIds)
            ->delete();

        $groupsDeleted = PublicGroup::query()
            ->whereIn('id', $groupIds)
            ->delete();

        return [
            'pivot_deleted' => $pivotDeleted,
            'positions_deleted' => $positionsDeleted,
            'departments_deleted' => $departmentsDeleted,
            'subgroups_deleted' => $subgroupsDeleted,
            'groups_deleted' => $groupsDeleted,
        ];
    }
}
