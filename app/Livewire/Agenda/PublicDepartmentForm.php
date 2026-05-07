<?php

namespace App\Livewire\Agenda;

use App\Models\Administration;
use App\Models\PrayerHouse;
use App\Models\PublicDepartment;
use App\Models\Regional;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class PublicDepartmentForm extends Component
{
    use AuthorizesRequests;

    public ?PublicDepartment $department = null;

    public string $scope = PublicDepartment::SCOPE_REGIONAL;

    public ?int $regional_id = null;

    public ?int $administration_id = null;

    public ?int $prayer_house_id = null;

    public string $name = '';

    public string $slug = '';

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
            $this->slug = $this->department->slug;
            $this->is_active = $this->department->is_active;
            $this->sort_order = (int) $this->department->sort_order;
        } else {
            $this->authorize('create', PublicDepartment::class);
            $user = auth()->user();
            $first = Regional::query()
                ->when(! $user->isSuperAdmin(), fn ($q) => $q->whereIn('id', $user->accessibleRegionalIds()))
                ->orderBy('name')
                ->first();
            $this->regional_id = $first?->id;
        }
    }

    public function updatedName(string $value): void
    {
        if (! $this->department && $this->slug === '') {
            $this->slug = Str::slug($value);
        }
    }

    public function save(): void
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
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('public_departments', 'slug')->ignore($this->department?->id),
            ],
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
        }

        $data = $this->validate($rules);

        if (! $this->assertScopeAccess($user, $data)) {
            return;
        }

        $payload = [
            'scope' => $data['scope'],
            'name' => $data['name'],
            'slug' => $data['slug'],
            'is_active' => $data['is_active'],
            'sort_order' => $data['sort_order'],
            'regional_id' => null,
            'administration_id' => null,
            'prayer_house_id' => null,
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

        return view('livewire.agenda.public-department-form', [
            'regionals' => $regionals,
            'administrations' => $administrations,
            'prayerHouses' => $prayerHouses,
        ]);
    }
}
