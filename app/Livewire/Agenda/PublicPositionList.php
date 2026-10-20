<?php

namespace App\Livewire\Agenda;

use App\Livewire\Concerns\DeletesPublicCatalogItems;
use App\Livewire\Concerns\UsesAccountPagination;
use App\Models\PublicPosition;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class PublicPositionList extends Component
{
    use AuthorizesRequests;
    use DeletesPublicCatalogItems;
    use UsesAccountPagination;
    use WithPagination;

    public string $departmentId = '';

    public function mount(): void
    {
        if (request()->filled('department')) {
            $this->departmentId = (string) request('department');
        }
    }

    public function render()
    {
        $this->authorize('viewAny', PublicPosition::class);

        $user = auth()->user();
        $query = PublicPosition::query()->with(['department', 'subgroup.group'])->orderBy('sort_order');

        if (! $user->isSuperAdmin()) {
            $ids = $user->accessibleRegionalIds();
            if ($ids === []) {
                $query->whereRaw('0 = 1');
            } else {
                $query->where(function ($outer) use ($ids) {
                    foreach ($ids as $rid) {
                        $outer->orWhere(fn ($q) => $q->forRegionalContext((int) $rid));
                    }
                });
            }
        }

        if ($this->departmentId !== '') {
            $query->where('public_department_id', (int) $this->departmentId);
        }

        return view('livewire.agenda.public-position-list', [
            'positions' => $query->paginate($this->paginatePerPage()),
        ]);
    }
}
