<?php

namespace App\Livewire\Infrastructure;

use App\Livewire\Concerns\UsesAccountPagination;
use App\Models\Administration;
use App\Models\Location;
use App\Models\Regional;
use App\Services\OrganizationalLocationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class LocationList extends Component
{
    use AuthorizesRequests;
    use UsesAccountPagination;
    use WithPagination;

    public string $search = '';

    public string $regionalId = '';

    public string $administrationId = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRegionalId(): void
    {
        $this->administrationId = '';
        $this->resetPage();
    }

    public function updatedAdministrationId(): void
    {
        $this->resetPage();
    }

    public function render(OrganizationalLocationService $locations)
    {
        $this->authorize('viewAny', Location::class);

        $user = auth()->user();
        $accessibleRegionalIds = $user->isSuperAdmin()
            ? null
            : $user->accessibleRegionalIds();

        $scoped = $locations->visibleLocationIdsForUser($user);

        $query = $locations->orderedLocationsQuery($scoped);

        if ($this->regionalId !== '') {
            $rid = (int) $this->regionalId;
            if ($accessibleRegionalIds === null || in_array($rid, $accessibleRegionalIds, true)) {
                $query->where(function ($q) use ($rid) {
                    $q->where('regional_id', $rid)
                        ->orWhereHas('administration', fn ($a) => $a->where('regional_id', $rid));
                });
            }
        }

        if ($this->administrationId !== '') {
            $query->where('administration_id', (int) $this->administrationId);
        }

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

        $regionals = Regional::query()
            ->where('is_active', true)
            ->when($accessibleRegionalIds !== null, fn ($q) => $q->whereIn('id', $accessibleRegionalIds))
            ->orderBy('name')
            ->limit(300)
            ->get();

        $administrations = Administration::query()
            ->with('regional')
            ->when($accessibleRegionalIds !== null, fn ($q) => $q->whereIn('regional_id', $accessibleRegionalIds))
            ->when($this->regionalId !== '', fn ($q) => $q->where('regional_id', (int) $this->regionalId))
            ->orderBy('name')
            ->limit(300)
            ->get();

        return view('livewire.infrastructure.location-list', [
            'locations' => $query->paginate($this->paginatePerPage())->withQueryString(),
            'regionals' => $regionals,
            'administrations' => $administrations,
        ]);
    }
}
