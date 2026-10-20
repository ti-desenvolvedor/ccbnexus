<?php

namespace App\Livewire\Organization;

use App\Models\Administration;
use App\Models\PrayerHouse;
use App\Services\OrganizationalContextService;
use App\Services\OrganizationalLocationService;
use App\Services\SlugService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Component;

class PrayerHouseForm extends Component
{
    use AuthorizesRequests;

    public ?PrayerHouse $prayerHouse = null;

    public int $administration_id = 0;

    public string $official_code = '';

    public string $name = '';

    public bool $is_active = true;

    public ?int $location_id = null;

    public function mount(OrganizationalContextService $context): void
    {
        if ($this->prayerHouse) {
            $this->authorize('update', $this->prayerHouse);
            $this->administration_id = $this->prayerHouse->administration_id;
            $this->official_code = (string) ($this->prayerHouse->official_code ?? '');
            $this->name = $this->prayerHouse->name;
            $this->is_active = $this->prayerHouse->is_active;
            $this->location_id = $this->prayerHouse->location_id;
        } else {
            $this->authorize('create', PrayerHouse::class);
            $user = auth()->user();
            $aid = $context->activeAdministrationId();
            if ($aid) {
                $this->administration_id = $aid;
            } else {
                $admin = Administration::query()
                    ->when(! $user->isSuperAdmin(), fn ($q) => $q->whereIn('regional_id', $user->accessibleRegionalIds()))
                    ->orderBy('name')
                    ->first();
                $this->administration_id = (int) ($admin?->id ?? 0);
            }
        }
    }

    public function updatedAdministrationId(): void
    {
        $allowed = app(OrganizationalLocationService::class)
            ->selectableIdsForAdministration((int) $this->administration_id);
        if ($this->location_id && ! in_array((int) $this->location_id, $allowed, true)) {
            $this->location_id = null;
        }
    }

    public function save(OrganizationalLocationService $locationScope, SlugService $slugs): void
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

        $codeRule = Rule::unique('prayer_houses', 'official_code');
        if ($this->prayerHouse) {
            $codeRule->ignore($this->prayerHouse->id);
        }

        $data = $this->validate([
            'administration_id' => ['required', 'exists:administrations,id'],
            'official_code' => ['nullable', 'string', 'max:32', 'regex:/^BR \d{2}-\d{4}$/', $codeRule],
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'location_id' => ['nullable', 'exists:locations,id'],
        ]);

        $data['official_code'] = $data['official_code'] !== '' && $data['official_code'] !== null
            ? $data['official_code']
            : null;

        $slug = $this->prayerHouse?->slug ?? $slugs->generate(
            $data['name'],
            'prayer_houses',
            ['administration_id' => (int) $data['administration_id']],
            $this->prayerHouse?->id
        );
        $data['slug'] = $slug;

        $allowed = $locationScope->mergeCurrentLocation(
            $locationScope->selectableIdsForAdministration((int) $data['administration_id']),
            $this->prayerHouse?->location_id
        );
        if (($data['location_id'] ?? null) && ! in_array((int) $data['location_id'], $allowed, true)) {
            $this->addError('location_id', __('Este local não pertence a esta administração.'));

            return;
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

    protected function locationOptions(OrganizationalLocationService $locationScope): Collection
    {
        $base = $this->administration_id > 0
            ? $locationScope->selectableIdsForAdministration((int) $this->administration_id)
            : [];
        $ids = $locationScope->mergeCurrentLocation($base, $this->location_id);

        return $locationScope->orderedLocationsQuery($ids)->limit(500)->get();
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

        return view('livewire.organization.prayer-house-form', [
            'administrations' => $administrations,
            'locations' => $this->locationOptions($locationScope),
        ]);
    }
}
