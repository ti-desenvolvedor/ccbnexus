<?php

namespace App\Livewire\Agenda;

use App\Models\Audience;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AudienceForm extends Component
{
    use AuthorizesRequests;

    public ?Audience $audience = null;

    public string $name = '';

    public string $slug = '';

    public bool $is_active = true;

    public int $sort_order = 0;

    public function mount(): void
    {
        if ($this->audience) {
            $this->authorize('update', $this->audience);
            $this->name = $this->audience->name;
            $this->slug = $this->audience->slug;
            $this->is_active = $this->audience->is_active;
            $this->sort_order = (int) $this->audience->sort_order;
        } else {
            $this->authorize('create', Audience::class);
        }
    }

    public function save(): void
    {
        if ($this->audience) {
            $this->authorize('update', $this->audience);
            $slugRule = Rule::unique('audiences', 'slug')->ignore($this->audience->id);
        } else {
            $this->authorize('create', Audience::class);
            $slugRule = Rule::unique('audiences', 'slug');
        }

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slugRule],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0', 'max:9999'],
        ]);

        if ($this->audience) {
            $this->audience->update($data);
            session()->flash('status', __('Público atualizado.'));
        } else {
            Audience::query()->create($data);
            session()->flash('status', __('Público criado.'));
        }

        $this->redirect(route('agenda.audiences.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.agenda.audience-form');
    }
}
