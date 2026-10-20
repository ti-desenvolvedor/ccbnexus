<?php

namespace App\Livewire\Agenda;

use App\Livewire\Concerns\DeletesPublicCatalogItems;
use App\Livewire\Concerns\UsesAccountPagination;
use App\Models\Administration;
use App\Models\PublicDepartment;
use App\Models\Regional;
use App\Services\OrganizationalContextService;
use App\Services\PublicAudienceCatalogService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class PublicDepartmentList extends Component
{
    use AuthorizesRequests;
    use DeletesPublicCatalogItems;
    use UsesAccountPagination;
    use WithPagination;

    public string $search = '';

    public string $scopeFilter = '';

    public string $regionalId = '';

    public string $administrationId = '';

    public string $subgroupId = '';

    public bool $activeOnly = false;

    public bool $grouped = false;

    /** Mostrar apenas os 17 departamentos do catálogo administrativo (Grupos I–V). */
    public bool $catalogAdministrativeOnly = true;

    public function mount(OrganizationalContextService $context): void
    {
        $this->syncRegionalFromContext($context);
    }

    #[On('nexus-context-updated')]
    public function onContextUpdated(OrganizationalContextService $context): void
    {
        $this->syncRegionalFromContext($context);
        $this->administrationId = '';
        $this->resetPage();
    }

    protected function syncRegionalFromContext(OrganizationalContextService $context): void
    {
        $rid = $context->activeRegionalId();
        if ($rid) {
            $this->regionalId = (string) $rid;
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingScopeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingRegionalId(): void
    {
        $this->administrationId = '';
        $this->resetPage();
    }

    public function render(PublicAudienceCatalogService $catalog)
    {
        $this->authorize('viewAny', PublicDepartment::class);

        $user = auth()->user();
        $query = $catalog->departmentsQueryForUser($user);

        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            $query->where('name', 'like', $term);
        }

        if ($this->scopeFilter !== '') {
            $query->where('scope', $this->scopeFilter);
        }

        if ($this->regionalId !== '') {
            $rid = (int) $this->regionalId;
            $query->where(fn ($q) => $q->forRegionalContext($rid));
        }

        if ($this->administrationId !== '') {
            $query->where('scope', PublicDepartment::SCOPE_ADMINISTRATION)
                ->where('administration_id', (int) $this->administrationId);
        }

        if ($this->subgroupId !== '') {
            $query->where('public_subgroup_id', (int) $this->subgroupId);
        }

        if ($this->activeOnly) {
            $query->where('is_active', true);
        }

        if ($this->catalogAdministrativeOnly) {
            $query->whereNotNull('public_subgroup_id')
                ->whereHas('subgroup', fn ($q) => $q->where('slug', 'like', 'grupo-%'));
        }

        $query->orderBy('sort_order')->orderBy('name');

        $departments = $this->grouped
            ? $query->get()->groupBy(fn (PublicDepartment $d) => $d->subgroup?->name ?? __('Sem grupo'))
            : null;

        $paginated = $this->grouped ? null : $query->paginate($this->paginatePerPage())->withQueryString();

        $regionals = $user->isSuperAdmin()
            ? Regional::query()->where('is_active', true)->orderBy('name')->get()
            : Regional::query()->whereIn('id', $user->accessibleRegionalIds())->where('is_active', true)->orderBy('name')->get();

        $administrations = collect();
        $subgroups = collect();

        if ($this->regionalId !== '') {
            $rid = (int) $this->regionalId;
            $administrations = Administration::query()
                ->where('regional_id', $rid)
                ->orderBy('name')
                ->get();
            $subgroups = $catalog->administrativeSubgroupsForRegional($rid);
        }

        return view('livewire.agenda.public-department-list', [
            'departments' => $paginated,
            'groupedDepartments' => $departments,
            'regionals' => $regionals,
            'administrations' => $administrations,
            'subgroups' => $subgroups,
        ]);
    }
}
