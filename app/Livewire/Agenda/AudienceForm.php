<?php

namespace App\Livewire\Agenda;

use App\Livewire\Concerns\DestroysPublicCatalogFormRecords;
use App\Models\Audience;
use App\Services\SlugService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class AudienceForm extends Component
{
    use AuthorizesRequests;
    use DestroysPublicCatalogFormRecords;

    public ?Audience $audience = null;

    public string $name = '';

    public bool $is_active = true;

    public int $sort_order = 0;

    public function mount(): void
    {
        if ($this->audience) {
            $this->authorize('update', $this->audience);
            $this->name = $this->audience->name;
            $this->is_active = $this->audience->is_active;
            $this->sort_order = (int) $this->audience->sort_order;
        } else {
            $this->authorize('create', Audience::class);
        }
    }

    public function save(SlugService $slugs): void
    {
        if ($this->audience) {
            $this->authorize('update', $this->audience);
        } else {
            $this->authorize('create', Audience::class);
        }

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0', 'max:9999'],
        ]);

        $data['slug'] = $this->audience?->slug ?? $slugs->generate(
            $data['name'],
            'audiences',
            [],
            $this->audience?->id
        );

        if ($this->audience) {
            $this->audience->update($data);
            session()->flash('status', __('Público atualizado.'));
        } else {
            Audience::query()->create($data);
            session()->flash('status', __('Público criado.'));
        }

        $this->redirect(route('agenda.audiences.index'), navigate: true);
    }

    public function destroyRecord(): void
    {
        if (! $this->audience) {
            return;
        }

        $this->authorize('delete', $this->audience);

        $this->handleCatalogDelete(
            function () {
                if ($this->audience->events()->exists()) {
                    throw ValidationException::withMessages([
                        'delete' => __('Não é possível excluir: esta audiência está ligada a eventos.'),
                    ]);
                }

                $this->audience->delete();
            },
            route('agenda.audiences.index'),
            __('Audiência excluída.'),
        );
    }

    public function render()
    {
        return view('livewire.agenda.audience-form');
    }
}
