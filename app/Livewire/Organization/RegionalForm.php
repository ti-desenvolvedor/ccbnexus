<?php

namespace App\Livewire\Organization;

use App\Models\Location;
use App\Models\Regional;
use App\Services\OrganizationalLocationService;
use App\Services\SlugService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Livewire\Component;

class RegionalForm extends Component
{
    use AuthorizesRequests;

    public ?Regional $regional = null;

    public string $name = '';

    public bool $is_active = true;

    public ?int $location_id = null;

    public function mount(): void
    {
        if ($this->regional) {
            $this->authorize('update', $this->regional);
            $this->name = $this->regional->name;
            $this->is_active = $this->regional->is_active;
            $this->location_id = $this->regional->location_id;
        } else {
            $this->authorize('create', Regional::class);
        }
    }

    public function save(OrganizationalLocationService $locationScope, SlugService $slugs): void
    {
        if ($this->regional) {
            $this->authorize('update', $this->regional);
        } else {
            $this->authorize('create', Regional::class);
        }

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'location_id' => ['nullable', 'exists:locations,id'],
        ]);

        $data['slug'] = $this->regional?->slug ?? $slugs->generate(
            $data['name'],
            'regionals',
            [],
            $this->regional?->id
        );

        $allowed = $this->regional
            ? $locationScope->mergeCurrentLocation(
                $locationScope->selectableIdsForRegional($this->regional->id),
                $this->regional->location_id
            )
            : $locationScope->mergeCurrentLocation(
                $locationScope->selectableIdsForNewRegional(),
                $this->location_id
            );

        if (($data['location_id'] ?? null) && ! in_array((int) $data['location_id'], $allowed, true)) {
            $this->addError('location_id', __('Este local não está disponível para esta regional.'));

            return;
        }

        if ($this->regional) {
            $this->regional->update($data);
            if ($this->regional->location_id) {
                Location::query()->where('id', $this->regional->location_id)->update([
                    'regional_id' => $this->regional->id,
                ]);
            }
            session()->flash('status', __('Regional atualizada.'));
        } else {
            $regional = Regional::query()->create($data);
            if ($regional->location_id) {
                Location::query()->where('id', $regional->location_id)->update([
                    'regional_id' => $regional->id,
                ]);
            }
            session()->flash('status', __('Regional criada.'));
        }

        $this->redirect(route('organization.regionals.index'), navigate: true);
    }

    protected function locationOptions(OrganizationalLocationService $locationScope): Collection
    {
        $ids = $this->regional
            ? $locationScope->mergeCurrentLocation(
                $locationScope->selectableIdsForRegional($this->regional->id),
                $this->location_id
            )
            : $locationScope->mergeCurrentLocation(
                $locationScope->selectableIdsForNewRegional(),
                $this->location_id
            );

        return $locationScope->orderedLocationsQuery($ids)->limit(500)->get();
    }

    public function render(OrganizationalLocationService $locationScope)
    {
        return view('livewire.organization.regional-form', [
            'locations' => $this->locationOptions($locationScope),
        ]);
    }
}
