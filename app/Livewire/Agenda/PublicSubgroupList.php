<?php

namespace App\Livewire\Agenda;

use App\Models\PublicGroup;
use App\Models\PublicSubgroup;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class PublicSubgroupList extends Component
{
    use AuthorizesRequests;
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
            ->paginate(20);

        return view('livewire.agenda.public-subgroup-list', [
            'subgroups' => $subgroups,
        ]);
    }
}
