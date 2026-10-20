<?php

namespace App\Livewire\Agenda;

use App\Livewire\Concerns\DestroysPublicCatalogFormRecords;
use App\Models\Administration;
use App\Models\PrayerHouse;
use App\Models\PublicDepartment;
use App\Models\Regional;
use App\Models\User;
use App\Services\PublicAudience\PublicAudienceCatalogDeleteService;
use App\Services\PublicAudienceCatalogService;
use App\Services\SlugService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class PublicDepartmentForm extends Component
{
    use AuthorizesRequests;
    use DestroysPublicCatalogFormRecords;

    public ?PublicDepartment $department = null;

    public string $scope = PublicDepartment::SCOPE_REGIONAL;

    public ?int $regional_id = null;

    public ?int $administration_id = null;

    public ?int $prayer_house_id = null;

    public ?int $public_subgroup_id = null;

    public string $name = '';

    public bool $is_active = true;

    public int $sort_order = 0;

    public function mount(): void
    {
        if ($this->department) {
            $this->authorize('update', $this->department);
            $this->scope = $this->department->scope;
            $this->regional_id = $this->department->regional_id;
            $this->administration_id = $this->department->administration_id;
            $this->prayer_house_id = $this->department->prayer_house_id;
            $this->name = $this->department->name;
            $this->is_active = $this->department->is_active;
            $this->sort_order = (int) $this->department->sort_order;
            $this->public_subgroup_id = $this->department->public_subgroup_id;
        } else {
            $this->authorize('create', PublicDepartment::class);
            $user = auth()->user();
            $first = Regional::query()
                ->when(! $user->isSuperAdmin(), fn ($q) => $q->whereIn('id', $user->accessibleRegionalIds()))
                ->orderBy('name')
                ->first();
            $this->regional_id = $first?->id;

            if (request()->has('scope')) {
                $this->scope = request()->string('scope')->toString();
            }
            if (request()->filled('regional_id')) {
                $this->regional_id = (int) request('regional_id');
            }
            if (request()->filled('administration_id')) {
                $this->administration_id = (int) request('administration_id');
            }
            if (request()->filled('public_subgroup_id')) {
                $this->public_subgroup_id = (int) request('public_subgroup_id');
            }
        }
    }

    public function save(SlugService $slugs): void
    {
        if ($this->department) {
            $this->authorize('update', $this->department);
        } else {
            $this->authorize('create', PublicDepartment::class);
        }

        $user = auth()->user();

        $rules = [
            'scope' => ['required', 'in:'.PublicDepartment::SCOPE_REGIONAL.','.PublicDepartment::SCOPE_ADMINISTRATION.','.PublicDepartment::SCOPE_PRAYER_HOUSE],
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0', 'max:9999'],
        ];

        if ($this->scope === PublicDepartment::SCOPE_REGIONAL) {
            $rules['regional_id'] = ['required', 'exists:regionals,id'];
            $rules['administration_id'] = ['nullable'];
            $rules['prayer_house_id'] = ['nullable'];
        } elseif ($this->scope === PublicDepartment::SCOPE_ADMINISTRATION) {
            $rules['administration_id'] = ['required', 'exists:administrations,id'];
            $rules['regional_id'] = ['nullable'];
            $rules['prayer_house_id'] = ['nullable'];
        } else {
            $rules['prayer_house_id'] = ['required', 'exists:prayer_houses,id'];
            $rules['regional_id'] = ['nullable'];
            $rules['administration_id'] = ['nullable'];
            $rules['public_subgroup_id'] = ['nullable', 'exists:public_subgroups,id'];
        }

        if (in_array($this->scope, [PublicDepartment::SCOPE_REGIONAL, PublicDepartment::SCOPE_ADMINISTRATION], true)) {
            $rules['public_subgroup_id'] = ['required', 'exists:public_subgroups,id'];
        }

        $data = $this->validate($rules);

        if ($this->public_subgroup_id === 0) {
            $data['public_subgroup_id'] = null;
        }

        if (! $this->assertScopeAccess($user, $data)) {
            return;
        }

        $slugScope = ['scope' => $data['scope']];
        if ($data['scope'] === PublicDepartment::SCOPE_REGIONAL) {
            $slugScope['regional_id'] = (int) $data['regional_id'];
            $slugScope['administration_id'] = null;
            $slugScope['prayer_house_id'] = null;
        } elseif ($data['scope'] === PublicDepartment::SCOPE_ADMINISTRATION) {
            $slugScope['administration_id'] = (int) $data['administration_id'];
            $slugScope['regional_id'] = null;
            $slugScope['prayer_house_id'] = null;
        } else {
            $slugScope['prayer_house_id'] = (int) $data['prayer_house_id'];
            $slugScope['regional_id'] = null;
            $slugScope['administration_id'] = null;
        }

        $payload = [
            'scope' => $data['scope'],
            'name' => $data['name'],
            'slug' => $this->department?->slug ?? $slugs->generate(
                $data['name'],
                'public_departments',
                $slugScope,
                $this->department?->id
            ),
            'is_active' => $data['is_active'],
            'sort_order' => $data['sort_order'],
            'regional_id' => null,
            'administration_id' => null,
            'prayer_house_id' => null,
            'public_subgroup_id' => $data['public_subgroup_id'] ?? null,
        ];

        if ($data['scope'] === PublicDepartment::SCOPE_REGIONAL) {
            $payload['regional_id'] = (int) $data['regional_id'];
        } elseif ($data['scope'] === PublicDepartment::SCOPE_ADMINISTRATION) {
            $payload['administration_id'] = (int) $data['administration_id'];
        } else {
            $payload['prayer_house_id'] = (int) $data['prayer_house_id'];
        }

        if ($this->department) {
            $this->department->update($payload);
            session()->flash('status', __('Departamento atualizado.'));
        } else {
            PublicDepartment::query()->create($payload);
            session()->flash('status', __('Departamento criado.'));
        }

        $this->redirect(route('agenda.public-departments.index'), navigate: true);
    }

    public function destroyRecord(PublicAudienceCatalogDeleteService $deleter, bool $withPositions = false): void
    {
        if (! $this->department) {
            return;
        }

        $this->authorize('delete', $this->department);

        $this->handleCatalogDelete(
            fn () => $deleter->deleteDepartment($this->department, $withPositions),
            route('agenda.public-departments.index'),
            __('Departamento excluído.'),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function assertScopeAccess(User $user, array $data): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($data['scope'] === PublicDepartment::SCOPE_REGIONAL) {
            if (! $user->canAccessRegional(Regional::query()->findOrFail((int) $data['regional_id']))) {
                $this->addError('regional_id', __('Sem permissão.'));

                return false;
            }
        } elseif ($data['scope'] === PublicDepartment::SCOPE_ADMINISTRATION) {
            $adm = Administration::query()->findOrFail((int) $data['administration_id']);
            if (! $user->canAccessAdministration($adm)) {
                $this->addError('administration_id', __('Sem permissão.'));

                return false;
            }
        } else {
            $house = PrayerHouse::query()->findOrFail((int) $data['prayer_house_id']);
            if (! $user->canAccessPrayerHouse($house)) {
                $this->addError('prayer_house_id', __('Sem permissão.'));

                return false;
            }
        }

        return true;
    }

    public function render()
    {
        $user = auth()->user();
        $regionals = $user->isSuperAdmin()
            ? Regional::query()->orderBy('name')->get()
            : Regional::query()->whereIn('id', $user->accessibleRegionalIds())->orderBy('name')->get();

        $administrations = $user->isSuperAdmin()
            ? Administration::query()->with('regional')->orderBy('name')->limit(500)->get()
            : Administration::query()->whereIn('regional_id', $user->accessibleRegionalIds())->with('regional')->orderBy('name')->limit(500)->get();

        $prayerHouses = $user->isSuperAdmin()
            ? PrayerHouse::query()->with('administration')->orderBy('name')->limit(500)->get()
            : PrayerHouse::query()->whereHas('administration', fn ($q) => $q->whereIn('regional_id', $user->accessibleRegionalIds()))->with('administration')->orderBy('name')->limit(500)->get();

        $catalog = app(PublicAudienceCatalogService::class);
        $regionalIdForSubgroups = $this->regional_id;
        if ($this->scope === PublicDepartment::SCOPE_ADMINISTRATION && $this->administration_id) {
            $regionalIdForSubgroups = Administration::query()
                ->whereKey($this->administration_id)
                ->value('regional_id');
        }
        $adminSubgroups = $regionalIdForSubgroups
            ? $catalog->administrativeSubgroupsForRegional((int) $regionalIdForSubgroups)
            : collect();

        return view('livewire.agenda.public-department-form', [
            'regionals' => $regionals,
            'administrations' => $administrations,
            'prayerHouses' => $prayerHouses,
            'adminSubgroups' => $adminSubgroups,
        ]);
    }
}
