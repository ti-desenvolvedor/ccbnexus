<?php

namespace App\Livewire\Agenda;

use App\Livewire\Concerns\DeletesPublicCatalogItems;
use App\Livewire\Concerns\UsesAccountPagination;
use App\Models\PublicDepartment;
use App\Models\PublicGroup;
use App\Models\PublicSubgroup;
use App\Services\PublicAudienceCatalogService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class PublicSubgroupDepartmentList extends Component
{
    use AuthorizesRequests;
    use DeletesPublicCatalogItems;
    use UsesAccountPagination;
    use WithPagination;

    public PublicGroup $group;

    public PublicSubgroup $subgroup;

    public string $scopeTab = PublicDepartment::SCOPE_REGIONAL;

    public function mount(PublicGroup $group, PublicSubgroup $subgroup): void
    {
        $this->group = $group;
        $this->subgroup = $subgroup;
        $this->group->loadMissing('regional');

        if ($this->subgroup->public_group_id !== $this->group->id) {
            abort(404);
        }

        $this->authorize('viewAny', PublicDepartment::class);

        $user = auth()->user();
        if (! $user->isSuperAdmin() && ! $user->canAccessRegional($group->regional)) {
            abort(403);
        }
    }

    public function render(PublicAudienceCatalogService $catalog)
    {
        $regionalId = $this->group->regional_id;

        $query = PublicDepartment::query()
            ->where('public_subgroup_id', $this->subgroup->id)
            ->with(['regional', 'administration', 'subgroup'])
            ->withCount('positions')
            ->orderBy('sort_order');

        if ($this->scopeTab === PublicDepartment::SCOPE_REGIONAL) {
            $query->where('scope', PublicDepartment::SCOPE_REGIONAL)
                ->where('regional_id', $regionalId);
        } else {
            $query->where('scope', PublicDepartment::SCOPE_ADMINISTRATION)
                ->whereHas('administration', fn ($q) => $q->where('regional_id', $regionalId));
        }

        return view('livewire.agenda.public-subgroup-department-list', [
            'departments' => $query->paginate($this->paginatePerPage())->withQueryString(),
        ]);
    }
}
