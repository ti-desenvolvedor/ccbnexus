<?php

namespace App\Livewire\Organization;

use App\Models\Administration;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class AdministrationList extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $this->authorize('viewAny', Administration::class);

        $user = auth()->user();
        $query = Administration::query()->with('regional')->orderBy('name');

        if (! $user->isSuperAdmin()) {
            $ids = $user->accessibleRegionalIds();
            if ($ids === []) {
                $query->whereRaw('0 = 1');
            } else {
                $query->whereIn('regional_id', $ids);
            }
        }

        if ($this->search !== '') {
            $s = '%'.$this->search.'%';
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', $s)->orWhere('slug', 'like', $s);
            });
        }

        return view('livewire.organization.administration-list', [
            'administrations' => $query->paginate(12),
        ]);
    }
}
