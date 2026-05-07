<?php

namespace App\Livewire\Organization;

use App\Models\Administration;
use App\Models\Location;
use App\Models\PrayerHouse;
use App\Services\OrganizationalLocationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;

class PrayerHouseForm extends Component
{
    use AuthorizesRequests;

    public ?PrayerHouse $prayerHouse = null;

    public int $administration_id = 0;

    public string $name = '';

    public string $slug = '';

    public bool $is_active = true;

    public ?int $location_id = null;

    public function mount(): void
    {
        if ($this->prayerHouse) {
            $this->authorize('update', $this->prayerHouse);
            $this->administration_id = $this->prayerHouse->administration_id;
            $this->name = $this->prayerHouse->name;
            $this->slug = $this->prayerHouse->slug;
            $this->is_active = $this->prayerHouse->is_active;
            $this->location_id = $this->prayerHouse->location_id;
        } else {
            $this->authorize('create', PrayerHouse::class);
            $user = auth()->user();
            $admin = Administration::query()
                ->when(! $user->isSuperAdmin(), fn ($q) => $q->whereIn('regional_id', $user->accessibleRegionalIds()))
                ->orderBy('name')
                ->first();
            $this->administration_id = (int) ($admin?->id ?? 0);
        }
    }

    public function save(OrganizationalLocationService $locationScope): void
    {
        if ($this->prayerHouse) {
            $this->authorize('update', $this->prayerHouse);
        } else {
            $this->authorize('create', PrayerHouse::class);
        }

        $admin = Administration::query()->findOrFail($this->administration_id);
        $user = auth()->user();
        if (! $user->isSuperAdmin() && ! $user->canAccessAdministration($admin)) {
            $this->addError('administration_id', __('Sem permissão para esta administração.'));

            return;
        }

        $slugRule = Rule::unique('prayer_houses', 'slug')
            ->where('administration_id', $this->administration_id);
        if ($this->prayerHouse) {
            $slugRule->ignore($this->prayerHouse->id);
        }

        $data = $this->validate([
            'administration_id' => ['required', 'exists:administrations,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slugRule],
            'is_active' => ['boolean'],
            'location_id' => ['nullable', 'exists:locations,id'],
        ]);

        if (! $user->isSuperAdmin() && ($data['location_id'] ?? null)) {
            $allowed = $locationScope->mergeCurrentLocation(
                $locationScope->selectableIdsForAdministration((int) $data['administration_id']),
                $this->prayerHouse?->location_id
            );
            if (! in_array((int) $data['location_id'], $allowed, true)) {
                $this->addError('location_id', __('Este local não pertence a esta administração ou às suas casas de oração.'));

                return;
            }
        }

        if ($this->prayerHouse) {
            $this->prayerHouse->update($data);
            session()->flash('status', __('Casa de oração atualizada.'));
        } else {
            PrayerHouse::query()->create($data);
            session()->flash('status', __('Casa de oração criada.'));
        }

        $this->redirect(route('organization.prayer-houses.index'), navigate: true);
    }

    public function render(OrganizationalLocationService $locationScope)
    {
        $user = auth()->user();
        $administrations = Administration::query()
            ->with('regional')
            ->when(! $user->isSuperAdmin(), fn ($q) => $q->whereIn('regional_id', $user->accessibleRegionalIds()))
            ->orderBy('name')
            ->limit(300)
            ->get();

        if ($user->isSuperAdmin()) {
            $locations = Location::query()->orderBy('name')->limit(500)->get();
        } else {
            $base = $this->administration_id > 0
                ? $locationScope->selectableIdsForAdministration((int) $this->administration_id)
                : [];
            $ids = $locationScope->mergeCurrentLocation($base, $this->location_id);
            $locations = $locationScope->orderedLocationsQuery($ids)->limit(500)->get();
        }

        return view('livewire.organization.prayer-house-form', [
            'administrations' => $administrations,
            'locations' => $locations,
        ]);
    }
}
