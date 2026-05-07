<?php

namespace App\Livewire\Infrastructure;

use App\Models\Location;
use App\Services\OrganizationalLocationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class LocationList extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render(OrganizationalLocationService $locations)
    {
        $this->authorize('viewAny', Location::class);

        $user = auth()->user();
        $scoped = $user && ! $user->isSuperAdmin()
            ? $locations->visibleLocationIdsForUser($user)
            : null;

        $query = $locations->orderedLocationsQuery($scoped);
        if ($this->search !== '') {
            $s = '%'.$this->search.'%';
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', $s)
                    ->orWhere('line1', 'like', $s)
                    ->orWhere('number', 'like', $s)
                    ->orWhere('district', 'like', $s)
                    ->orWhere('city', 'like', $s)
                    ->orWhere('postal_code', 'like', $s);
            });
        }

        return view('livewire.infrastructure.location-list', [
            'locations' => $query->paginate(12),
        ]);
    }
}
