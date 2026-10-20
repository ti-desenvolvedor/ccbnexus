<?php

namespace App\Livewire\Agenda;

use App\Livewire\Concerns\DestroysPublicCatalogFormRecords;
use App\Models\PublicGroup;
use App\Models\PublicSubgroup;
use App\Services\PublicAudience\PublicAudienceCatalogDeleteService;
use App\Services\SlugService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class PublicSubgroupForm extends Component
{
    use AuthorizesRequests;
    use DestroysPublicCatalogFormRecords;

    public PublicGroup $group;

    public ?PublicSubgroup $publicSubgroup = null;

    public string $name = '';

    public bool $is_active = true;

    public int $sort_order = 0;

    public function mount(PublicGroup $group, ?int $publicSubgroupId = null): void
    {
        $this->group = $group;
        $this->publicSubgroup = $publicSubgroupId ? PublicSubgroup::findOrFail($publicSubgroupId) : null;

        $user = auth()->user();
        if (! $user->isSuperAdmin() && ! $user->canAccessRegional($group->regional)) {
            abort(403);
        }

        if ($this->publicSubgroup) {
            $this->authorize('update', $this->publicSubgroup);
            if ($this->publicSubgroup->public_group_id !== $this->group->id) {
                abort(404);
            }
            $this->name = $this->publicSubgroup->name;
            $this->is_active = $this->publicSubgroup->is_active;
            $this->sort_order = (int) $this->publicSubgroup->sort_order;
        } else {
            $this->authorize('create', PublicSubgroup::class);
        }
    }

    public function save(SlugService $slugs): void
    {
        if ($this->publicSubgroup) {
            $this->authorize('update', $this->publicSubgroup);
        } else {
            $this->authorize('create', PublicSubgroup::class);
        }

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0', 'max:9999'],
        ]);

        $data['slug'] = $this->publicSubgroup?->slug ?? $slugs->generate(
            $data['name'],
            'public_subgroups',
            ['public_group_id' => $this->group->id],
            $this->publicSubgroup?->id
        );

        $payload = array_merge($data, ['public_group_id' => $this->group->id]);

        if ($this->publicSubgroup) {
            $this->publicSubgroup->update($payload);
            session()->flash('status', __('Subgrupo atualizado.'));
        } else {
            PublicSubgroup::query()->create($payload);
            session()->flash('status', __('Subgrupo criado.'));
        }

        $this->redirect(route('agenda.public-subgroups.index', $this->group), navigate: true);
    }

    public function destroyRecord(PublicAudienceCatalogDeleteService $deleter): void
    {
        if (! $this->publicSubgroup) {
            return;
        }

        $this->authorize('delete', $this->publicSubgroup);

        $this->handleCatalogDelete(
            fn () => $deleter->deleteSubgroup($this->publicSubgroup),
            route('agenda.public-subgroups.index', $this->group),
            __('Subgrupo excluído.'),
        );
    }

    public function render()
    {
        return view('livewire.agenda.public-subgroup-form');
    }
}
