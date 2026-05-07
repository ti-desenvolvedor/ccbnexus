<?php

namespace App\Livewire\Organization;

use App\Models\Location;
use App\Models\Regional;
use App\Services\OrganizationalLocationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class RegionalForm extends Component
{
    use AuthorizesRequests;

    public ?Regional $regional = null;

    public string $name = '';

    public string $slug = '';

    public bool $is_active = true;

    public ?int $location_id = null;

    public function mount(): void
    {
        if ($this->regional) {
            $this->authorize('update', $this->regional);
            $this->name = $this->regional->name;
            $this->slug = $this->regional->slug;
            $this->is_active = $this->regional->is_active;
            $this->location_id = $this->regional->location_id;
        } else {
            $this->authorize('create', Regional::class);
        }
    }

    public function save(OrganizationalLocationService $locationScope): void
    {
        if ($this->regional) {
            $this->authorize('update', $this->regional);
        } else {
            $this->authorize('create', Regional::class);
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'is_active' => ['boolean'],
            'location_id' => ['nullable', 'exists:locations,id'],
        ];

        if ($this->regional) {
            $rules['slug'][] = 'unique:regionals,slug,'.$this->regional->id;
        } else {
            $rules['slug'][] = 'unique:regionals,slug';
        }

        $data = $this->validate($rules);

        $user = auth()->user();
        if (! $user->isSuperAdmin() && ($data['location_id'] ?? null)) {
            $allowed = $this->regional
                ? $locationScope->mergeCurrentLocation(
                    $locationScope->selectableIdsForRegional($this->regional->id),
                    $this->regional->location_id
                )
                : $locationScope->orphanLocationIds();
            if (! in_array((int) $data['location_id'], $allowed, true)) {
                $this->addError('location_id', __('Este local não está disponível para esta regional.'));

                return;
            }
        }

        if ($this->regional) {
            $this->regional->update($data);
            session()->flash('status', __('Regional atualizada.'));
        } else {
            Regional::query()->create($data);
            session()->flash('status', __('Regional criada.'));
        }

        $this->redirect(route('organization.regionals.index'), navigate: true);
    }

    public function render(OrganizationalLocationService $locationScope)
    {
        $user = auth()->user();
        if ($user->isSuperAdmin()) {
            $locations = Location::query()->orderBy('name')->limit(500)->get();
        } elseif ($this->regional) {
            $ids = $locationScope->mergeCurrentLocation(
                $locationScope->selectableIdsForRegional($this->regional->id),
                $this->location_id
            );
            $locations = $locationScope->orderedLocationsQuery($ids)->limit(500)->get();
        } else {
            $ids = $locationScope->mergeCurrentLocation(
                $locationScope->orphanLocationIds(),
                $this->location_id
            );
            $locations = $locationScope->orderedLocationsQuery($ids)->limit(500)->get();
        }

        return view('livewire.organization.regional-form', [
            'locations' => $locations,
        ]);
    }
}
