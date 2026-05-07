<?php

namespace App\Livewire\Users;

use App\Models\Administration;
use App\Models\PrayerHouse;
use App\Models\Regional;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class UserForm extends Component
{
    use AuthorizesRequests;

    public ?User $user = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public ?int $regional_id = null;

    public ?int $administration_id = null;

    public ?int $prayer_house_id = null;

    /** @var array<int, string> */
    public array $role_names = [];

    public bool $is_super_admin = false;

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        if ($this->user) {
            $this->authorize('update', $this->user);
            $this->user->load(['roles', 'regional', 'administration', 'prayerHouse']);
            $this->name = $this->user->name;
            $this->email = $this->user->email;
            $this->phone = (string) ($this->user->phone ?? '');
            $this->regional_id = $this->user->regional_id;
            $this->administration_id = $this->user->administration_id;
            $this->prayer_house_id = $this->user->prayer_house_id;
            $this->role_names = $this->user->roles->pluck('name')->all();
            $this->is_super_admin = $this->user->is_super_admin;
        } else {
            $this->authorize('create', User::class);
            $auth = auth()->user();
            if ($auth && ! $auth->isSuperAdmin()) {
                $first = $auth->accessibleRegionalIds()[0] ?? null;
                $this->regional_id = $first;
            }
        }
    }

    public function updatedRegionalId(): void
    {
        $this->administration_id = null;
        $this->prayer_house_id = null;
    }

    public function updatedAdministrationId(): void
    {
        $this->prayer_house_id = null;
    }

    public function save(): void
    {
        $auth = auth()->user();
        if ($this->user) {
            $this->authorize('update', $this->user);
        } else {
            $this->authorize('create', User::class);
        }

        $roleNames = Role::query()->where('guard_name', 'web')->pluck('name')->all();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user?->id)],
            'phone' => ['nullable', 'string', 'max:40'],
            'regional_id' => ['nullable', 'exists:regionals,id'],
            'administration_id' => ['nullable', 'exists:administrations,id'],
            'prayer_house_id' => ['nullable', 'exists:prayer_houses,id'],
            'role_names' => ['array'],
            'role_names.*' => ['string', Rule::in($roleNames)],
        ];

        if ($this->user) {
            $rules['password'] = ['nullable', 'string', 'min:8', 'confirmed'];
        } else {
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
        }

        if ($auth->isSuperAdmin()) {
            $rules['is_super_admin'] = ['boolean'];
        }

        $data = $this->validate($rules);

        if ($this->administration_id && ! $this->regional_id) {
            $this->regional_id = Administration::query()->find($this->administration_id)?->regional_id;
        }
        if ($this->prayer_house_id && ! $this->administration_id) {
            $this->administration_id = PrayerHouse::query()->find($this->prayer_house_id)?->administration_id;
        }

        if (! $this->validateOrganizationalChain()) {
            return;
        }

        if (! $this->user && ! $auth->isSuperAdmin() && ! $this->regional_id) {
            $this->addError('regional_id', __('Selecione a regional para o novo utilizador.'));

            return;
        }

        $this->assertRegionalAccess($auth);

        if (! $auth->isSuperAdmin()) {
            $data['is_super_admin'] = false;
        }

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'regional_id' => $data['regional_id'] ?? null,
            'administration_id' => $data['administration_id'] ?? null,
            'prayer_house_id' => $data['prayer_house_id'] ?? null,
            'is_super_admin' => (bool) ($data['is_super_admin'] ?? false),
        ];

        if ($this->user) {
            if (! empty($data['password'])) {
                $payload['password'] = Hash::make($data['password']);
            }
            $this->user->update($payload);
            $this->user->syncRoles($data['role_names'] ?? []);
            session()->flash('status', __('Utilizador atualizado.'));
        } else {
            $payload['password'] = Hash::make($data['password']);
            $payload['email_verified_at'] = now();
            $new = User::query()->create($payload);
            $new->syncRoles($data['role_names'] ?? []);
            session()->flash('status', __('Utilizador criado.'));
        }

        $this->redirect(route('users.index'), navigate: true);
    }

    protected function assertRegionalAccess(User $auth): void
    {
        foreach (['regional_id' => Regional::class, 'administration_id' => Administration::class, 'prayer_house_id' => PrayerHouse::class] as $field => $model) {
            $id = $this->{$field};
            if (! $id) {
                continue;
            }
            $entity = $model::query()->find($id);
            if (! $entity) {
                continue;
            }
            if ($field === 'regional_id' && $auth->isSuperAdmin()) {
                continue;
            }
            if ($field === 'regional_id' && $entity instanceof Regional && ! $auth->canAccessRegional($entity)) {
                abort(403, __('Sem permissão para esta regional.'));
            }
            if ($field === 'administration_id' && $entity instanceof Administration && ! $auth->canAccessAdministration($entity)) {
                abort(403, __('Sem permissão para esta administração.'));
            }
            if ($field === 'prayer_house_id' && $entity instanceof PrayerHouse && ! $auth->canAccessPrayerHouse($entity)) {
                abort(403, __('Sem permissão para esta casa de oração.'));
            }
        }
    }

    protected function validateOrganizationalChain(): bool
    {
        if ($this->administration_id) {
            $adm = Administration::query()->find($this->administration_id);
            if ($adm && $this->regional_id && (int) $adm->regional_id !== (int) $this->regional_id) {
                $this->addError('administration_id', __('A administração não pertence à regional selecionada.'));

                return false;
            }
        }
        if ($this->prayer_house_id) {
            $house = PrayerHouse::query()->find($this->prayer_house_id);
            if ($house && $this->administration_id && (int) $house->administration_id !== (int) $this->administration_id) {
                $this->addError('prayer_house_id', __('A casa de oração não pertence à administração selecionada.'));

                return false;
            }
        }

        return true;
    }

    public function render()
    {
        $auth = auth()->user();
        $regionals = $auth->isSuperAdmin()
            ? Regional::query()->orderBy('name')->get()
            : Regional::query()->whereIn('id', $auth->accessibleRegionalIds())->orderBy('name')->get();

        $administrations = collect();
        if ($this->regional_id) {
            $administrations = Administration::query()
                ->where('regional_id', $this->regional_id)
                ->orderBy('name')
                ->get();
        }

        $prayerHouses = collect();
        if ($this->administration_id) {
            $prayerHouses = PrayerHouse::query()
                ->where('administration_id', $this->administration_id)
                ->orderBy('name')
                ->get();
        }

        return view('livewire.users.user-form', [
            'regionals' => $regionals,
            'administrations' => $administrations,
            'prayerHouses' => $prayerHouses,
            'roles' => Role::query()->where('guard_name', 'web')->orderBy('name')->get(),
        ]);
    }
}
