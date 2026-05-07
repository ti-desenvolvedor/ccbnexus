<?php

namespace App\Livewire\Agenda;

use App\Models\PublicGroup;
use App\Models\Regional;
use App\Services\OrganizationalContextService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class PublicGroupList extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public ?int $filter_regional_id = null;

    public function mount(OrganizationalContextService $context): void
    {
        $this->authorize('viewAny', PublicGroup::class);
        $this->filter_regional_id = $context->activeRegionalId();
    }

    public function updatingFilterRegionalId(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();
        $query = PublicGroup::query()->with('regional')->orderBy('sort_order');

        if (! $user->isSuperAdmin()) {
            $query->whereIn('regional_id', $user->accessibleRegionalIds());
        }

        if ($this->filter_regional_id) {
            $query->where('regional_id', $this->filter_regional_id);
        }

        $regionals = $user->isSuperAdmin()
            ? Regional::query()->orderBy('name')->get()
            : Regional::query()->whereIn('id', $user->accessibleRegionalIds())->orderBy('name')->get();

        return view('livewire.agenda.public-group-list', [
            'groups' => $query->paginate(15),
            'regionals' => $regionals,
        ]);
    }
}
