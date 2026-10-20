<?php

namespace App\Livewire\Secretaria;

use App\Models\RegionalSpecialCollection;
use App\Services\OrganizationalContextService;
use Livewire\Attributes\On;
use Livewire\Component;

class SpecialCollectionManager extends Component
{
    public string $mode = 'programada';

    public string $title = '';

    public string $body = '';

    public ?string $collection_on = null;

    public string $valid_from = '';

    public string $valid_until = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('editar_evento') || auth()->user()?->can('visualizar_relatorios'), 403);
        $this->valid_from = now()->toDateString();
        $this->valid_until = now()->addDays(14)->toDateString();
    }

    #[On('nexus-context-updated')]
    public function onContextUpdated(): void
    {
        //
    }

    public function save(OrganizationalContextService $context): void
    {
        abort_unless(auth()->user()?->canMaintainSecretaria(), 403);
        $rid = $context->activeRegionalId();
        abort_unless($rid, 403);
        $data = $this->validate([
            'mode' => ['required', 'in:programada,urgente'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
            'collection_on' => ['nullable', 'date'],
            'valid_from' => ['required', 'date'],
            'valid_until' => ['required', 'date', 'after_or_equal:valid_from'],
        ]);
        RegionalSpecialCollection::query()->create(array_merge($data, [
            'regional_id' => $rid,
            'is_active' => true,
        ]));
        $this->title = '';
        $this->body = '';
        session()->flash('status', __('Coleta registada.'));
    }

    public function render(OrganizationalContextService $context)
    {
        $rid = $context->activeRegionalId();

        return view('livewire.secretaria.special-collection-manager', [
            'rows' => $rid
                ? RegionalSpecialCollection::query()->where('regional_id', $rid)->orderByDesc('id')->get()
                : collect(),
        ]);
    }
}
