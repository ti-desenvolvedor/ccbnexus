<?php

namespace App\Livewire\Agenda;

use App\Models\PublicGroup;
use App\Models\PublicSubgroup;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class PublicSubgroupForm extends Component
{
    use AuthorizesRequests;

    public PublicGroup $group;

    public ?PublicSubgroup $publicSubgroup = null;

    public string $name = '';

    public string $slug = '';

    public bool $is_active = true;

    public int $sort_order = 0;

    public function mount(PublicGroup $group, ?PublicSubgroup $publicSubgroup = null): void
    {
        $this->group = $group;
        $this->publicSubgroup = $publicSubgroup;

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
            $this->slug = $this->publicSubgroup->slug;
            $this->is_active = $this->publicSubgroup->is_active;
            $this->sort_order = (int) $this->publicSubgroup->sort_order;
        } else {
            $this->authorize('create', PublicSubgroup::class);
        }
    }

    public function updatedName(string $value): void
    {
        if (! $this->publicSubgroup && $this->slug === '') {
            $this->slug = Str::slug($value);
        }
    }

    public function save(): void
    {
        if ($this->publicSubgroup) {
            $this->authorize('update', $this->publicSubgroup);
        } else {
            $this->authorize('create', PublicSubgroup::class);
        }

        $slugRule = Rule::unique('public_subgroups', 'slug')->where('public_group_id', $this->group->id);
        if ($this->publicSubgroup) {
            $slugRule->ignore($this->publicSubgroup->id);
        }

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slugRule],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0', 'max:9999'],
        ]);

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

    public function render()
    {
        return view('livewire.agenda.public-subgroup-form');
    }
}
