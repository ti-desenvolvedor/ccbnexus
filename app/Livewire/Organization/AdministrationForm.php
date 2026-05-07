<?php

namespace App\Livewire\Organization;

use App\Models\Administration;
use App\Models\Location;
use App\Models\Regional;
use App\Services\OrganizationalLocationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AdministrationForm extends Component
{
    use AuthorizesRequests;

    public ?Administration $administration = null;

    public int $regional_id = 0;

    public string $name = '';

    public string $slug = '';

    public bool $is_active = true;

    public ?int $location_id = null;

    public function mount(): void
    {
        if ($this->administration) {
            $this->authorize('update', $this->administration);
            $this->regional_id = $this->administration->regional_id;
            $this->name = $this->administration->name;
            $this->slug = $this->administration->slug;
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

    public function save(OrganizationalLocationService $locationScope): void
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

        $slugRule = Rule::unique('administrations', 'slug')
            ->where('regional_id', $this->regional_id);
        if ($this->administration) {
            $slugRule->ignore($this->administration->id);
        }

        $data = $this->validate([
            'regional_id' => ['required', 'exists:regionals,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slugRule],
            'is_active' => ['boolean'],
            'location_id' => ['nullable', 'exists:locations,id'],
        ]);

        if (! $user->isSuperAdmin() && ($data['location_id'] ?? null)) {
            $allowed = $this->administration
                ? $locationScope->mergeCurrentLocation(
                    $locationScope->selectableIdsForAdministration($this->administration->id),
                    $this->administration->location_id
                )
                : $locationScope->selectableIdsForNewAdministration((int) $data['regional_id']);
            if (! in_array((int) $data['location_id'], $allowed, true)) {
                $this->addError('location_id', __('Este local não pertence ao contexto desta administração.'));

                return;
            }
        }

        if ($this->administration) {
            $this->administration->update($data);
            session()->flash('status', __('Administração atualizada.'));
        } else {
            Administration::query()->create($data);
            session()->flash('status', __('Administração criada.'));
        }

        $this->redirect(route('organization.administrations.index'), navigate: true);
    }

    public function render(OrganizationalLocationService $locationScope)
    {
        $user = auth()->user();
        $regionals = $user->isSuperAdmin()
            ? Regional::query()->orderBy('name')->get()
            : Regional::query()->whereIn('id', $user->accessibleRegionalIds())->orderBy('name')->get();

        if ($user->isSuperAdmin()) {
            $locations = Location::query()->orderBy('name')->limit(500)->get();
        } elseif ($this->administration) {
            $ids = $locationScope->mergeCurrentLocation(
                $locationScope->selectableIdsForAdministration($this->administration->id),
                $this->location_id
            );
            $locations = $locationScope->orderedLocationsQuery($ids)->limit(500)->get();
        } else {
            $ids = $locationScope->mergeCurrentLocation(
                $locationScope->selectableIdsForNewAdministration((int) $this->regional_id),
                $this->location_id
            );
            $locations = $locationScope->orderedLocationsQuery($ids)->limit(500)->get();
        }

        return view('livewire.organization.administration-form', [
            'regionals' => $regionals,
            'locations' => $locations,
        ]);
    }
}
