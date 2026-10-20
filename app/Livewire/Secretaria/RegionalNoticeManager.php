<?php

namespace App\Livewire\Secretaria;

use App\Models\RegionalNotice;
use App\Services\OrganizationalContextService;
use Livewire\Attributes\On;
use Livewire\Component;

class RegionalNoticeManager extends Component
{
    public string $title = '';

    public string $body = '';

    public string $category = 'geral';

    public string $valid_from = '';

    public string $valid_until = '';

    public bool $is_standing = false;

    public int $sort_order = 100;

    public ?int $editingId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('editar_evento') || auth()->user()?->can('visualizar_relatorios'), 403);
        $this->valid_from = now()->toDateString();
        $this->valid_until = now()->addDays(30)->toDateString();
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
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
            'category' => ['required', 'in:geral,bras,circular'],
            'valid_from' => ['required', 'date'],
            'valid_until' => ['required', 'date', 'after_or_equal:valid_from'],
            'is_standing' => ['boolean'],
            'sort_order' => ['integer', 'min:0', 'max:9999'],
        ]);
        $payload = array_merge($data, [
            'regional_id' => $rid,
            'is_active' => true,
            'is_standing' => (bool) $this->is_standing,
            'sort_order' => (int) $this->sort_order,
        ]);
        if ($this->editingId) {
            RegionalNotice::query()->where('regional_id', $rid)->whereKey($this->editingId)->firstOrFail()->update($payload);
        } else {
            RegionalNotice::query()->create($payload);
        }
        $this->resetForm();
        session()->flash('status', __('Aviso guardado.'));
    }

    public function edit(int $id, OrganizationalContextService $context): void
    {
        abort_unless(auth()->user()?->canMaintainSecretaria(), 403);
        $n = RegionalNotice::query()->where('regional_id', $context->activeRegionalId())->findOrFail($id);
        $this->editingId = $n->id;
        $this->title = $n->title;
        $this->body = $n->body;
        $this->category = $n->category;
        $this->valid_from = $n->valid_from->toDateString();
        $this->valid_until = $n->valid_until->toDateString();
        $this->is_standing = (bool) $n->is_standing;
        $this->sort_order = (int) $n->sort_order;
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->title = '';
        $this->body = '';
        $this->category = 'geral';
        $this->valid_from = now()->toDateString();
        $this->valid_until = now()->addDays(30)->toDateString();
        $this->is_standing = false;
        $this->sort_order = 100;
    }

    public function render(OrganizationalContextService $context)
    {
        $rid = $context->activeRegionalId();

        return view('livewire.secretaria.regional-notice-manager', [
            'notices' => $rid
                ? RegionalNotice::query()
                    ->where('regional_id', $rid)
                    ->orderByDesc('is_standing')
                    ->orderBy('sort_order')
                    ->orderByDesc('valid_from')
                    ->get()
                : collect(),
        ]);
    }
}
