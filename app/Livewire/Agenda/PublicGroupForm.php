<?php

namespace App\Livewire\Agenda;

use App\Livewire\Concerns\DestroysPublicCatalogFormRecords;
use App\Models\PublicGroup;
use App\Models\Regional;
use App\Services\PublicAudience\PublicAudienceCatalogDeleteService;
use App\Services\SlugService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class PublicGroupForm extends Component
{
    use AuthorizesRequests;
    use DestroysPublicCatalogFormRecords;

    public ?PublicGroup $publicGroup = null;

    public int $regional_id = 0;

    public string $name = '';

    public bool $is_active = true;

    public int $sort_order = 0;

    public function mount(): void
    {
        if ($this->publicGroup) {
            $this->authorize('update', $this->publicGroup);
            $this->regional_id = $this->publicGroup->regional_id;
            $this->name = $this->publicGroup->name;
            $this->is_active = $this->publicGroup->is_active;
            $this->sort_order = (int) $this->publicGroup->sort_order;
        } else {
            $this->authorize('create', PublicGroup::class);
            $user = auth()->user();
            $first = Regional::query()
                ->when(! $user->isSuperAdmin(), fn ($q) => $q->whereIn('id', $user->accessibleRegionalIds()))
                ->orderBy('name')
                ->first();
            $this->regional_id = (int) ($first?->id ?? 0);
        }
    }

    public function save(SlugService $slugs): void
    {
        if ($this->publicGroup) {
            $this->authorize('update', $this->publicGroup);
        } else {
            $this->authorize('create', PublicGroup::class);
        }

        $user = auth()->user();
        if (! $user->isSuperAdmin() && ! $user->canAccessRegional(Regional::query()->findOrFail($this->regional_id))) {
            $this->addError('regional_id', __('Sem permissão para esta regional.'));

            return;
        }

        $data = $this->validate([
            'regional_id' => ['required', 'exists:regionals,id'],
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0', 'max:9999'],
        ]);

        $data['slug'] = $this->publicGroup?->slug ?? $slugs->generate(
            $data['name'],
            'public_groups',
            ['regional_id' => (int) $data['regional_id']],
            $this->publicGroup?->id
        );

        if ($this->publicGroup) {
            $this->publicGroup->update($data);
            session()->flash('status', __('Grupo atualizado.'));
        } else {
            PublicGroup::query()->create($data);
            session()->flash('status', __('Grupo criado.'));
        }

        $this->redirect(route('agenda.public-groups.index'), navigate: true);
    }

    public function destroyRecord(PublicAudienceCatalogDeleteService $deleter): void
    {
        if (! $this->publicGroup) {
            return;
        }

        $this->authorize('delete', $this->publicGroup);

        $this->handleCatalogDelete(
            fn () => $deleter->deleteGroup($this->publicGroup),
            route('agenda.public-groups.index'),
            __('Grupo excluído.'),
        );
    }

    public function render()
    {
        $user = auth()->user();
        $regionals = $user->isSuperAdmin()
            ? Regional::query()->orderBy('name')->get()
            : Regional::query()->whereIn('id', $user->accessibleRegionalIds())->orderBy('name')->get();

        return view('livewire.agenda.public-group-form', [
            'regionals' => $regionals,
        ]);
    }
}
