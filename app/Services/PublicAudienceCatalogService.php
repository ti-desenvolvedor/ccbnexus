<?php

namespace App\Services;

use App\Models\PublicDepartment;
use App\Models\PublicPosition;
use App\Models\PublicSubgroup;
use App\Models\Regional;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PublicAudienceCatalogService
{
    /**
     * IDs de cargos que o utilizador pode associar a eventos desta regional.
     */
    public function allowedPositionIdsForRegional(User $user, int $regionalId): array
    {
        if (! $user->isSuperAdmin() && ! $user->canAccessRegional(Regional::query()->findOrFail($regionalId))) {
            abort(403);
        }

        return PublicPosition::query()
            ->active()
            ->forRegionalContext($regionalId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Cargos ativos para a aba Público do evento, agrupados pelo nome do grupo (ou "Outros").
     */
    public function positionsGroupedForEventForm(User $user, ?int $regionalId): Collection
    {
        if (! $regionalId) {
            return collect();
        }

        if (! $user->isSuperAdmin() && ! $user->canAccessRegional(Regional::query()->findOrFail($regionalId))) {
            abort(403);
        }

        $positions = PublicPosition::query()
            ->active()
            ->forRegionalContext($regionalId)
            ->with(['department', 'subgroup.group'])
            ->orderBy('sort_order')
            ->get();

        return $positions->groupBy(function (PublicPosition $p) {
            $name = $p->subgroup?->group?->name;

            return $name ?: __('Outros');
        });
    }

    /**
     * Departamentos visíveis para montar cargos (CRUD), por escopo do utilizador.
     */
    public function departmentsForUser(User $user): Collection
    {
        $query = PublicDepartment::query()->orderBy('scope')->orderBy('name');

        if ($user->isSuperAdmin()) {
            return $query->get();
        }

        $ids = $user->accessibleRegionalIds();
        if ($ids === []) {
            return collect();
        }

        return $query->where(function (Builder $outer) use ($ids) {
            foreach ($ids as $rid) {
                $outer->orWhere(fn (Builder $q) => $q->forRegionalContext((int) $rid));
            }
        })->get();
    }

    /**
     * Subgrupos para o formulário de cargos (mesmo critério de escopo que grupos).
     */
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
}
