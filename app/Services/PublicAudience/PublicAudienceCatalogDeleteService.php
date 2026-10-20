<?php

namespace App\Services\PublicAudience;

use App\Models\PublicDepartment;
use App\Models\PublicGroup;
use App\Models\PublicPosition;
use App\Models\PublicSubgroup;
use Illuminate\Validation\ValidationException;

class PublicAudienceCatalogDeleteService
{
    public function deletePosition(PublicPosition $position): void
    {
        $position->delete();
    }

    public function deleteDepartment(PublicDepartment $department, bool $withPositions = false): void
    {
        if ($department->positions()->exists()) {
            if (! $withPositions) {
                throw ValidationException::withMessages([
                    'delete' => __('Existem cargos vinculados. Exclua-os primeiro ou use «Excluir com cargos».'),
                ]);
            }

            $department->positions()->each(fn (PublicPosition $position) => $position->delete());
        }

        $department->delete();
    }

    public function deleteSubgroup(PublicSubgroup $subgroup): void
    {
        if (PublicDepartment::query()->where('public_subgroup_id', $subgroup->id)->exists()) {
            throw ValidationException::withMessages([
                'delete' => __('Não é possível excluir: existem departamentos vinculados a este subgrupo.'),
            ]);
        }

        if ($subgroup->positions()->exists()) {
            throw ValidationException::withMessages([
                'delete' => __('Não é possível excluir: existem cargos vinculados a este subgrupo.'),
            ]);
        }

        $subgroup->delete();
    }

    public function deleteGroup(PublicGroup $group): void
    {
        if ($group->slug === PublicCatalogOrganogramSeeder::ADMIN_GROUP_SLUG) {
            throw ValidationException::withMessages([
                'delete' => __('O grupo base «Colaboradores administrativos» não pode ser excluído.'),
            ]);
        }

        if ($group->subgroups()->exists()) {
            throw ValidationException::withMessages([
                'delete' => __('Não é possível excluir: existem subgrupos vinculados. Exclua-os primeiro.'),
            ]);
        }

        $group->delete();
    }
}
