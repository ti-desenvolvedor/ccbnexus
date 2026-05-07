<?php

namespace App\Livewire\Infrastructure;

use App\Models\Parking;
use App\Services\OrganizationalLocationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class ParkingList extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public function render(OrganizationalLocationService $locationScope)
    {
        $this->authorize('viewAny', Parking::class);

        $user = auth()->user();
        $q = Parking::query()->with('location')->orderBy('name');
        if ($user && ! $user->isSuperAdmin()) {
            $ids = $locationScope->visibleLocationIdsForUser($user);
            if ($ids !== null) {
                if ($ids === []) {
                    $q->whereRaw('0 = 1');
                } else {
                    $q->whereIn('location_id', $ids);
                }
            }
        }

        return view('livewire.infrastructure.parking-list', [
            'parkings' => $q->paginate(15),
        ]);
    }
}
