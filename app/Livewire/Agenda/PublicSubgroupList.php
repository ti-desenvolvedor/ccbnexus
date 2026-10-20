<?php

namespace App\Livewire\Agenda;

use App\Livewire\Concerns\DeletesPublicCatalogItems;
use App\Livewire\Concerns\UsesAccountPagination;
use App\Models\PublicGroup;
use App\Models\PublicSubgroup;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class PublicSubgroupList extends Component
{
    use AuthorizesRequests;
    use DeletesPublicCatalogItems;
    use UsesAccountPagination;
    use WithPagination;

    public PublicGroup $group;

    public function mount(PublicGroup $group): void
    {
        $this->group = $group;
        $this->group->loadMissing('regional');
        $this->authorize('viewAny', PublicSubgroup::class);
        $user = auth()->user();
        if (! $user->isSuperAdmin() && ! $user->canAccessRegional($group->regional)) {
            abort(403);
        }
    }

    public function render()
    {
        $subgroups = PublicSubgroup::query()
            ->where('public_group_id', $this->group->id)
            ->orderBy('sort_order')
            ->paginate($this->paginatePerPage());

        return view('livewire.agenda.public-subgroup-list', [
            'subgroups' => $subgroups,
        ]);
    }
}
