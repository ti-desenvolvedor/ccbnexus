<?php

namespace App\Livewire\Organization;

use App\Models\Administration;
use App\Models\Location;
use App\Models\Regional;
use App\Services\OrganizationalLocationService;
use App\Services\SlugService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Livewire\Component;

class AdministrationForm extends Component
{
    use AuthorizesRequests;

    public ?Administration $administration = null;

    public int $regional_id = 0;

    public string $name = '';

    public bool $is_active = true;

    public ?int $location_id = null;

    public function mount(): void
    {
        if ($this->administration) {
            $this->authorize('update', $this->administration);
            $this->regional_id = $this->administration->regional_id;
            $this->name = $this->administration->name;
            $this->is_active = $this->administration->is_active;
            $this->location_id = $this->administration->location_id;
        } else {
            $this->authorize('create', Administration::class);
            $user = auth()->user();
            $first = Regional::query()
                ->when(! $user->isSuperAdmin(), fn ($q) => $q->whereIn('id', $user->accessibleRegionalIds()))
                ->orderBy('name')
                ->first();
            $this->regional_id = (int) ($first?->id ?? 0);
        }
    }

    public function updatedRegionalId(): void
    {
        if (! $this->administration) {
            $allowed = app(OrganizationalLocationService::class)
                ->selectableIdsForNewAdministration((int) $this->regional_id);
            if ($this->location_id && ! in_array((int) $this->location_id, $allowed, true)) {
                $this->location_id = null;
            }
        }
    }

    public function save(OrganizationalLocationService $locationScope, SlugService $slugs): void
    {
        if ($this->administration) {
            $this->authorize('update', $this->administration);
        } else {
            $this->authorize('create', Administration::class);
        }

        $regional = Regional::query()->findOrFail($this->regional_id);
        $user = auth()->user();
        if (! $user->isSuperAdmin() && ! $user->canAccessRegional($regional)) {
            $this->addError('regional_id', __('Sem permissão para esta regional.'));

            return;
        }

        $data = $this->validate([
            'regional_id' => ['required', 'exists:regionals,id'],
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'location_id' => ['nullable', 'exists:locations,id'],
        ]);

        $data['slug'] = $this->administration?->slug ?? $slugs->generate(
            $data['name'],
            'administrations',
            ['regional_id' => (int) $data['regional_id']],
            $this->administration?->id
        );

        $allowed = $this->administration
            ? $locationScope->mergeCurrentLocation(
                $locationScope->selectableIdsForAdministration($this->administration->id),
                $this->administration->location_id
            )
            : $locationScope->mergeCurrentLocation(
                $locationScope->selectableIdsForNewAdministration((int) $data['regional_id']),
                $this->location_id
            );

        if (($data['location_id'] ?? null) && ! in_array((int) $data['location_id'], $allowed, true)) {
            $this->addError('location_id', __('Este local não pertence ao contexto desta administração.'));

            return;
        }

        if ($this->administration) {
            $this->administration->update($data);
            session()->flash('status', __('Administração atualizada.'));
        } else {
            $admin = Administration::query()->create($data);
            if ($admin->location_id) {
                Location::query()->where('id', $admin->location_id)->update([
                    'administration_id' => $admin->id,
                ]);
            }
            session()->flash('status', __('Administração criada.'));
            $this->redirect(route('organization.administrations.index'), navigate: true);

            return;
        }

        if ($this->administration->location_id) {
            Location::query()->where('id', $this->administration->location_id)->update([
                'administration_id' => $this->administration->id,
            ]);
        }

        $this->redirect(route('organization.administrations.index'), navigate: true);
    }

    protected function locationOptions(OrganizationalLocationService $locationScope): Collection
    {
        if ($this->administration) {
            $ids = $locationScope->mergeCurrentLocation(
                $locationScope->selectableIdsForAdministration($this->administration->id),
                $this->location_id
            );
        } else {
            $ids = $locationScope->mergeCurrentLocation(
                $locationScope->selectableIdsForNewAdministration((int) $this->regional_id),
                $this->location_id
            );
        }

        return $locationScope->orderedLocationsQuery($ids)->limit(500)->get();
    }

    public function render(OrganizationalLocationService $locationScope)
    {
        $user = auth()->user();
        $regionals = $user->isSuperAdmin()
            ? Regional::query()->orderBy('name')->get()
            : Regional::query()->whereIn('id', $user->accessibleRegionalIds())->orderBy('name')->get();

        return view('livewire.organization.administration-form', [
            'regionals' => $regionals,
            'locations' => $this->locationOptions($locationScope),
        ]);
    }
}
