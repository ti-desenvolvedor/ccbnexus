<?php

namespace App\Livewire\Infrastructure;

use App\Models\Administration;
use App\Models\Location;
use App\Models\Regional;
use App\Services\CepLookupService;
use App\Services\IbgeMunicipioService;
use App\Services\OrganizationalContextService;
use App\Support\BrazilianStates;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;

class LocationForm extends Component
{
    use AuthorizesRequests;

    public ?Location $location = null;

    public ?int $administration_id = null;

    public ?int $regional_id = null;

    public bool $is_regional_hq = false;

    public string $name = '';

    public string $line1 = '';

    public string $number = '';

    public string $complement = '';

    public string $district = '';

    public string $city = '';

    public string $state = '';

    public string $postal_code = '';

    public string $country = 'BR';

    /** @var list<string> */
    public array $cityOptions = [];

    public bool $loadingCep = false;

    public bool $loadingCities = false;

    public ?string $address_feedback = null;

    public function mount(OrganizationalContextService $context): void
    {
        if ($this->location) {
            $this->authorize('update', $this->location);
            $this->administration_id = $this->location->administration_id;
            $this->regional_id = $this->location->regional_id;
            $this->is_regional_hq = $this->location->administration_id === null && $this->location->regional_id !== null;
            $this->name = $this->location->name;
            $this->line1 = (string) $this->location->line1;
            $this->number = (string) ($this->location->number ?? '');
            $this->complement = (string) ($this->location->complement ?? '');
            $this->district = (string) ($this->location->district ?? '');
            $this->city = (string) $this->location->city;
            $this->state = $this->normalizeBrazilianUf((string) $this->location->state);
            $this->postal_code = (string) $this->location->postal_code;
            $this->country = $this->location->country ?? 'BR';
        } else {
            $this->authorize('create', Location::class);
            $prefillAdmin = request()->integer('administration_id') ?: $context->activeAdministrationId();
            if ($prefillAdmin) {
                $this->administration_id = $prefillAdmin;
            }
        }

        if ($this->country === 'BR' && BrazilianStates::isValidUf($this->state)) {
            $this->cityOptions = app(IbgeMunicipioService::class)->municipalityNamesForUf($this->state);
        }
    }

    public function fetchCep(CepLookupService $cepService, IbgeMunicipioService $ibge): void
    {
        $this->address_feedback = null;
        if ($this->country !== 'BR') {
            return;
        }

        $this->loadingCep = true;
        $result = $cepService->lookup($this->postal_code);
        $this->loadingCep = false;

        if ($result === null) {
            $this->address_feedback = __('CEP não encontrado. Use 8 dígitos.');

            return;
        }

        if (! empty($result['line1'])) {
            $this->line1 = $result['line1'];
        }
        if (! empty($result['complement'])) {
            $this->complement = $result['complement'];
        }
        if (! empty($result['district'])) {
            $this->district = $result['district'];
        }
        if (! empty($result['postal_code'])) {
            $this->postal_code = $result['postal_code'];
        }
        if (! empty($result['state'])) {
            $this->state = $result['state'];
            $this->loadCityOptions($ibge);
        }
        if (! empty($result['city']) && $this->cityOptions !== []) {
            $via = $result['city'];
            if (in_array($via, $this->cityOptions, true)) {
                $this->city = $via;
            } else {
                $match = collect($this->cityOptions)->first(
                    fn (string $n) => mb_strtolower($n) === mb_strtolower($via)
                );
                $this->city = $match ?? '';
            }
        }

        $this->address_feedback = __('Dados preenchidos a partir do CEP. Confira antes de guardar.');
    }

    public function updatedState(IbgeMunicipioService $ibge): void
    {
        $this->state = strtoupper(substr($this->state, 0, 2));
        if ($this->country !== 'BR') {
            return;
        }
        $this->loadCityOptions($ibge);
    }

    public function updatedCountry(IbgeMunicipioService $ibge): void
    {
        if ($this->country === 'BR' && BrazilianStates::isValidUf($this->state)) {
            $this->loadCityOptions($ibge);
        } else {
            $this->cityOptions = [];
        }
    }

    protected function loadCityOptions(IbgeMunicipioService $ibge): void
    {
        $this->address_feedback = null;
        if ($this->country !== 'BR' || ! BrazilianStates::isValidUf($this->state)) {
            $this->cityOptions = [];
            $this->city = '';

            return;
        }

        $this->loadingCities = true;
        $this->cityOptions = $ibge->municipalityNamesForUf($this->state);
        $this->loadingCities = false;

        if ($this->city !== '' && ! in_array($this->city, $this->cityOptions, true)) {
            $this->city = '';
        }
    }

    public function save(): void
    {
        if ($this->location) {
            $this->authorize('update', $this->location);
        } else {
            $this->authorize('create', Location::class);
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'line1' => ['nullable', 'string', 'max:255'],
            'number' => ['nullable', 'string', 'max:32'],
            'complement' => ['nullable', 'string', 'max:120'],
            'district' => ['nullable', 'string', 'max:120'],
            'country' => ['required', 'string', 'size:2'],
            'postal_code' => ['nullable', 'string', 'max:32'],
            'is_regional_hq' => ['boolean'],
            'administration_id' => ['nullable', 'exists:administrations,id'],
            'regional_id' => ['nullable', 'exists:regionals,id'],
        ];

        if ($this->country === 'BR') {
            $rules['state'] = ['nullable', Rule::in(array_keys(BrazilianStates::all()))];
            if ($this->cityOptions !== []) {
                $rules['city'] = ['nullable', 'string', 'max:120', Rule::in($this->cityOptions)];
            } else {
                $rules['city'] = ['nullable', 'string', 'max:120'];
            }
        } else {
            $rules['state'] = ['nullable', 'string', 'max:8'];
            $rules['city'] = ['nullable', 'string', 'max:120'];
        }

        $data = $this->validate($rules);
        $user = auth()->user();

        if ($data['is_regional_hq']) {
            if (! $data['regional_id']) {
                $this->addError('regional_id', __('Selecione a regional para a sede.'));

                return;
            }
            if (! $user->isSuperAdmin() && ! in_array((int) $data['regional_id'], $user->accessibleRegionalIds(), true)) {
                $this->addError('regional_id', __('Sem permissão para esta regional.'));

                return;
            }
            $data['administration_id'] = null;
            $data['regional_id'] = (int) $data['regional_id'];
        } else {
            if (! $data['administration_id']) {
                $this->addError('administration_id', __('Selecione a administração deste local.'));

                return;
            }
            $admin = Administration::query()->findOrFail($data['administration_id']);
            if (! $user->isSuperAdmin() && ! $user->canAccessAdministration($admin)) {
                $this->addError('administration_id', __('Sem permissão para esta administração.'));

                return;
            }
            $data['administration_id'] = (int) $data['administration_id'];
            $data['regional_id'] = null;
        }

        unset($data['is_regional_hq']);

        if ($this->location) {
            $this->location->update($data);
            session()->flash('status', __('Local atualizado.'));
        } else {
            Location::query()->create($data);
            session()->flash('status', __('Local criado.'));
        }

        $this->redirect(route('infrastructure.locations.index'), navigate: true);
    }

    public function render()
    {
        $user = auth()->user();
        $administrations = Administration::query()
            ->with('regional')
            ->when(! $user->isSuperAdmin(), fn ($q) => $q->whereIn('regional_id', $user->accessibleRegionalIds()))
            ->orderBy('name')
            ->limit(300)
            ->get();
        $regionals = $user->isSuperAdmin()
            ? Regional::query()->orderBy('name')->get()
            : Regional::query()->whereIn('id', $user->accessibleRegionalIds())->orderBy('name')->get();

        return view('livewire.infrastructure.location-form', [
            'brazilianStates' => BrazilianStates::all(),
            'administrations' => $administrations,
            'regionals' => $regionals,
        ]);
    }

    private function normalizeBrazilianUf(string $raw): string
    {
        $raw = strtoupper(trim($raw));
        if (strlen($raw) === 2 && BrazilianStates::isValidUf($raw)) {
            return $raw;
        }
        if (strlen($raw) >= 2) {
            $two = substr($raw, 0, 2);
            if (BrazilianStates::isValidUf($two)) {
                return $two;
            }
        }

        return '';
    }
}
