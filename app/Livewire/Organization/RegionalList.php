<?php

namespace App\Livewire\Organization;

use App\Models\Regional;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class RegionalList extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    protected $paginationTheme = 'tailwind';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $this->authorize('viewAny', Regional::class);

        $user = auth()->user();
        $query = Regional::query()->orderBy('name');

        if (! $user->isSuperAdmin()) {
            $ids = $user->accessibleRegionalIds();
            if ($ids === []) {
                $query->whereRaw('0 = 1');
            } else {
                $query->whereIn('id', $ids);
            }
        }

        if ($this->search !== '') {
            $s = '%'.$this->search.'%';
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', $s)->orWhere('slug', 'like', $s);
            });
        }

        return view('livewire.organization.regional-list', [
            'regionals' => $query->paginate(12),
        ]);
    }
}
