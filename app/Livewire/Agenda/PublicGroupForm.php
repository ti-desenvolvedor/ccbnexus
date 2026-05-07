<?php

namespace App\Livewire\Agenda;

use App\Models\PublicGroup;
use App\Models\Regional;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class PublicGroupForm extends Component
{
    use AuthorizesRequests;

    public ?PublicGroup $publicGroup = null;

    public int $regional_id = 0;

    public string $name = '';

    public string $slug = '';

    public bool $is_active = true;

    public int $sort_order = 0;

    public function mount(): void
    {
        if ($this->publicGroup) {
            $this->authorize('update', $this->publicGroup);
            $this->regional_id = $this->publicGroup->regional_id;
            $this->name = $this->publicGroup->name;
            $this->slug = $this->publicGroup->slug;
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

    public function updatedName(string $value): void
    {
        if (! $this->publicGroup && $this->slug === '') {
            $this->slug = Str::slug($value);
        }
    }

    public function save(): void
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

        $slugRule = Rule::unique('public_groups', 'slug')->where('regional_id', $this->regional_id);
        if ($this->publicGroup) {
            $slugRule->ignore($this->publicGroup->id);
        }

        $data = $this->validate([
            'regional_id' => ['required', 'exists:regionals,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slugRule],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0', 'max:9999'],
        ]);

        if ($this->publicGroup) {
            $this->publicGroup->update($data);
            session()->flash('status', __('Grupo atualizado.'));
        } else {
            PublicGroup::query()->create($data);
            session()->flash('status', __('Grupo criado.'));
        }

        $this->redirect(route('agenda.public-groups.index'), navigate: true);
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
