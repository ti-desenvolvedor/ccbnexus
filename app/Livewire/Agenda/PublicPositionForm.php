<?php

namespace App\Livewire\Agenda;

use App\Livewire\Concerns\DestroysPublicCatalogFormRecords;
use App\Models\PublicPosition;
use App\Services\PublicAudience\PublicAudienceCatalogDeleteService;
use App\Services\PublicAudienceCatalogService;
use App\Services\SlugService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class PublicPositionForm extends Component
{
    use AuthorizesRequests;
    use DestroysPublicCatalogFormRecords;

    public ?PublicPosition $position = null;

    public int $public_department_id = 0;

    public ?int $public_subgroup_id = null;

    public string $name = '';

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
            $this->is_active = $this->position->is_active;
            $this->sort_order = (int) $this->position->sort_order;
            $this->is_department_coordinator = (bool) $this->position->is_department_coordinator;
        } else {
            $this->authorize('create', PublicPosition::class);
            $first = app(PublicAudienceCatalogService::class)->departmentsForUser(Auth::user())->first();
            $this->public_department_id = (int) ($first?->id ?? 0);
        }
    }

    public function save(PublicAudienceCatalogService $catalog, SlugService $slugs): void
    {
        if ($this->position) {
            $this->authorize('update', $this->position);
        } else {
            $this->authorize('create', PublicPosition::class);
        }

        $allowedDeptIds = $catalog->departmentsForUser(auth()->user())->pluck('id')->all();

        if ($this->public_subgroup_id === '' || $this->public_subgroup_id === 0) {
            $this->public_subgroup_id = null;
        }

        $data = $this->validate([
            'public_department_id' => ['required', 'integer', Rule::in($allowedDeptIds)],
            'public_subgroup_id' => ['nullable', 'integer', 'exists:public_subgroups,id'],
            'name' => ['required', 'string', 'max:255'],
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

        $data['slug'] = $this->position?->slug ?? $slugs->generate(
            $data['name'],
            'public_positions',
            ['public_department_id' => (int) $data['public_department_id']],
            $this->position?->id
        );

        if ($this->position) {
            $this->position->update($data);
            session()->flash('status', __('Cargo atualizado.'));
        } else {
            PublicPosition::query()->create($data);
            session()->flash('status', __('Cargo criado.'));
        }

        $this->redirect(route('agenda.public-positions.index'), navigate: true);
    }

    public function destroyRecord(PublicAudienceCatalogDeleteService $deleter): void
    {
        if (! $this->position) {
            return;
        }

        $this->authorize('delete', $this->position);

        $this->handleCatalogDelete(
            fn () => $deleter->deletePosition($this->position),
            route('agenda.public-positions.index'),
            __('Cargo excluído.'),
        );
    }

    public function render(PublicAudienceCatalogService $catalog)
    {
        return view('livewire.agenda.public-position-form', [
            'departments' => $catalog->departmentsForUser(auth()->user()),
            'subgroups' => $catalog->subgroupsForUser(auth()->user()),
        ]);
    }
}
