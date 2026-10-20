<?php

namespace App\Livewire\Organization;

use App\Livewire\Concerns\UsesAccountPagination;
use App\Models\Administration;
use App\Models\PrayerHouse;
use App\Models\Regional;
use App\Services\OrganizationalContextService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class PrayerHouseList extends Component
{
    use AuthorizesRequests;
    use UsesAccountPagination;
    use WithPagination;

    public string $search = '';

    public string $regionalId = '';

    public string $administrationId = '';

    /** '', 'active' ou 'inactive' */
    public string $status = '';

    public function mount(OrganizationalContextService $context): void
    {
        $rid = $context->activeRegionalId();
        if ($rid) {
            $this->regionalId = (string) $rid;
        }

        $aid = $context->activeAdministrationId();
        if ($aid) {
            $this->administrationId = (string) $aid;
        }
    }

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

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->regionalId = '';
        $this->administrationId = '';
        $this->status = '';
        $this->resetPage();
    }

    public function render()
    {
        $this->authorize('viewAny', PrayerHouse::class);

        $user = auth()->user();
        $accessibleRegionalIds = $user->isSuperAdmin()
            ? null
            : $user->accessibleRegionalIds();

        $query = PrayerHouse::query()
            ->with(['administration.regional', 'location'])
            ->orderByRaw('official_code IS NULL')
            ->orderBy('official_code')
            ->orderBy('name');

        if ($accessibleRegionalIds !== null) {
            if ($accessibleRegionalIds === []) {
                $query->whereRaw('0 = 1');
            } else {
                $query->whereHas('administration', fn ($q) => $q->whereIn('regional_id', $accessibleRegionalIds));
            }
        }

        if ($this->regionalId !== '') {
            $rid = (int) $this->regionalId;
            if ($accessibleRegionalIds === null || in_array($rid, $accessibleRegionalIds, true)) {
                $query->whereHas('administration', fn ($q) => $q->where('regional_id', $rid));
            }
        }

        if ($this->administrationId !== '') {
            $aid = (int) $this->administrationId;
            $adminQ = Administration::query()->where('id', $aid);
            if ($accessibleRegionalIds !== null) {
                $adminQ->whereIn('regional_id', $accessibleRegionalIds);
            }
            if ($this->regionalId !== '') {
                $adminQ->where('regional_id', (int) $this->regionalId);
            }
            if ($adminQ->exists()) {
                $query->where('administration_id', $aid);
            }
        }

        if ($this->status === 'active') {
            $query->where('is_active', true);
        } elseif ($this->status === 'inactive') {
            $query->where('is_active', false);
        }

        if ($this->search !== '') {
            $s = '%'.$this->search.'%';
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', $s)
                    ->orWhere('slug', 'like', $s)
                    ->orWhere('official_code', 'like', $s);
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

        return view('livewire.organization.prayer-house-list', [
            'prayerHouses' => $query->paginate($this->paginatePerPage())->withQueryString(),
            'regionals' => $regionals,
            'administrations' => $administrations,
        ]);
    }
}
