<?php

namespace App\Livewire\Agenda;

use App\Models\PublicDepartment;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class PublicDepartmentList extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public function render()
    {
        $this->authorize('viewAny', PublicDepartment::class);

        $user = auth()->user();
        $query = PublicDepartment::query()
            ->with(['regional', 'administration', 'prayerHouse'])
            ->orderBy('scope')
            ->orderBy('name');

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

        return view('livewire.agenda.public-department-list', [
            'departments' => $query->paginate(20),
        ]);
    }
}
