<?php

namespace App\Livewire\Organization;

use App\Models\PrayerHouse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class PrayerHouseList extends Component
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
        $this->authorize('viewAny', PrayerHouse::class);

        $user = auth()->user();
        $query = PrayerHouse::query()->with('administration.regional')->orderBy('name');

        if (! $user->isSuperAdmin()) {
            $ids = $user->accessibleRegionalIds();
            if ($ids === []) {
                $query->whereRaw('0 = 1');
            } else {
                $query->whereHas('administration', fn ($q) => $q->whereIn('regional_id', $ids));
            }
        }

        if ($this->search !== '') {
            $s = '%'.$this->search.'%';
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', $s)->orWhere('slug', 'like', $s);
            });
        }

        return view('livewire.organization.prayer-house-list', [
            'prayerHouses' => $query->paginate(12),
        ]);
    }
}
