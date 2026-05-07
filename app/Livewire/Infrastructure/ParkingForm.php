<?php

namespace App\Livewire\Infrastructure;

use App\Models\Location;
use App\Models\Parking;
use App\Services\OrganizationalLocationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class ParkingForm extends Component
{
    use AuthorizesRequests;

    public ?Parking $parking = null;

    public int $location_id = 0;

    public string $name = '';

    public ?int $capacity = null;

    public function mount(): void
    {
        if ($this->parking) {
            $this->authorize('update', $this->parking);
            $this->location_id = $this->parking->location_id;
            $this->name = $this->parking->name;
            $this->capacity = $this->parking->capacity;
        } else {
            $this->authorize('create', Parking::class);
            $user = auth()->user();
            if ($user->isSuperAdmin()) {
                $this->location_id = (int) (Location::query()->value('id') ?? 0);
            } else {
                $svc = app(OrganizationalLocationService::class);
                $ids = $svc->visibleLocationIdsForUser($user) ?? [];
                $this->location_id = (int) (collect($ids)->first() ?? Location::query()->value('id') ?? 0);
            }
        }
    }

    public function save(OrganizationalLocationService $locationScope): void
    {
        if ($this->parking) {
            $this->authorize('update', $this->parking);
        } else {
            $this->authorize('create', Parking::class);
        }

        $data = $this->validate([
            'location_id' => ['required', 'exists:locations,id'],
            'name' => ['required', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:1'],
        ]);

        $user = auth()->user();
        if (! $user->isSuperAdmin()) {
            $visible = $locationScope->visibleLocationIdsForUser($user);
            if (! is_array($visible) || ! in_array((int) $data['location_id'], $visible, true)) {
                $this->addError('location_id', __('Este local está fora do seu âmbito organizacional.'));

                return;
            }
        }

        if ($this->parking) {
            $this->parking->update($data);
            session()->flash('status', __('Estacionamento atualizado.'));
        } else {
            Parking::query()->create($data);
            session()->flash('status', __('Estacionamento criado.'));
        }

        $this->redirect(route('infrastructure.parkings.index'), navigate: true);
    }

    public function render(OrganizationalLocationService $locationScope)
    {
        $user = auth()->user();
        if ($user->isSuperAdmin()) {
            $locations = Location::query()->orderBy('name')->limit(500)->get();
        } else {
            $ids = $locationScope->visibleLocationIdsForUser($user) ?? [];
            $ids = $locationScope->mergeCurrentLocation($ids, $this->location_id ?: null);
            $locations = $locationScope->orderedLocationsQuery($ids)->limit(500)->get();
        }

        return view('livewire.infrastructure.parking-form', [
            'locations' => $locations,
        ]);
    }
}
