<?php

namespace App\Livewire\Agenda;

use App\Models\PublicPosition;
use App\Services\PublicAudienceCatalogService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class PublicPositionForm extends Component
{
    use AuthorizesRequests;

    public ?PublicPosition $position = null;

    public int $public_department_id = 0;

    public ?int $public_subgroup_id = null;

    public string $name = '';

    public string $slug = '';

    public bool $is_active = true;

    public int $sort_order = 0;

    public bool $is_department_coordinator = false;

    public function mount(): void
    {
        if ($this->position) {
            $this->authorize('update', $this->position);
            $this->public_department_id = $this->position->public_department_id;
            $this->public_subgroup_id = $this->position->public_subgroup_id;
            $this->name = $this->position->name;
            $this->slug = $this->position->slug;
            $this->is_active = $this->position->is_active;
            $this->sort_order = (int) $this->position->sort_order;
            $this->is_department_coordinator = (bool) $this->position->is_department_coordinator;
        } else {
            $this->authorize('create', PublicPosition::class);
            $first = app(PublicAudienceCatalogService::class)->departmentsForUser(Auth::user())->first();
            $this->public_department_id = (int) ($first?->id ?? 0);
        }
    }

    public function updatedName(string $value): void
    {
        if (! $this->position && $this->slug === '') {
            $this->slug = Str::slug($value);
        }
    }

    public function save(PublicAudienceCatalogService $catalog): void
    {
        if ($this->position) {
            $this->authorize('update', $this->position);
        } else {
            $this->authorize('create', PublicPosition::class);
        }

        $allowedDeptIds = $catalog->departmentsForUser(auth()->user())->pluck('id')->all();

        $slugRule = Rule::unique('public_positions', 'slug')->where('public_department_id', $this->public_department_id);
        if ($this->position) {
            $slugRule->ignore($this->position->id);
        }

        if ($this->public_subgroup_id === '' || $this->public_subgroup_id === 0) {
            $this->public_subgroup_id = null;
        }

        $data = $this->validate([
            'public_department_id' => ['required', 'integer', Rule::in($allowedDeptIds)],
            'public_subgroup_id' => ['nullable', 'integer', 'exists:public_subgroups,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slugRule],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0', 'max:9999'],
            'is_department_coordinator' => ['boolean'],
        ]);

        if ($data['public_subgroup_id'] ?? null) {
            $allowedSubgroupIds = $catalog->subgroupsForUser(auth()->user())->pluck('id')->all();
            if (! in_array((int) $data['public_subgroup_id'], $allowedSubgroupIds, true)) {
                $this->addError('public_subgroup_id', __('Subgrupo inválido.'));

                return;
            }
        }

        if ($this->position) {
            $this->position->update($data);
            session()->flash('status', __('Cargo atualizado.'));
        } else {
            PublicPosition::query()->create($data);
            session()->flash('status', __('Cargo criado.'));
        }

        $this->redirect(route('agenda.public-positions.index'), navigate: true);
    }

    public function render(PublicAudienceCatalogService $catalog)
    {
        return view('livewire.agenda.public-position-form', [
            'departments' => $catalog->departmentsForUser(auth()->user()),
            'subgroups' => $catalog->subgroupsForUser(auth()->user()),
        ]);
    }
}
