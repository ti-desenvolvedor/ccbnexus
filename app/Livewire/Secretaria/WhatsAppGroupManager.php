<?php

namespace App\Livewire\Secretaria;

use App\Models\WhatsAppGroup;
use App\Services\OrganizationalContextService;
use App\Services\SlugService;
use Livewire\Attributes\On;
use Livewire\Component;

class WhatsAppGroupManager extends Component
{
    public string $name = '';

    public string $invite_hint = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('gerenciar_avisos') || auth()->user()?->can('editar_evento'), 403);
    }

    #[On('nexus-context-updated')]
    public function onContextUpdated(): void
    {
        //
    }

    public function save(OrganizationalContextService $context, SlugService $slugs): void
    {
        $rid = $context->activeRegionalId();
        abort_unless($rid, 403);
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'invite_hint' => ['nullable', 'string', 'max:255'],
        ]);
        $slug = $slugs->generate($data['name'], 'whatsapp_groups', ['regional_id' => $rid]);
        WhatsAppGroup::query()->create([
            'regional_id' => $rid,
            'slug' => $slug,
            'name' => $data['name'],
            'invite_hint' => $data['invite_hint'] ?: null,
            'is_active' => true,
        ]);
        $this->name = '';
        $this->invite_hint = '';
        session()->flash('status', __('Grupo guardado.'));
    }

    public function render(OrganizationalContextService $context)
    {
        $rid = $context->activeRegionalId();

        return view('livewire.secretaria.whatsapp-group-manager', [
            'groups' => $rid
                ? WhatsAppGroup::query()->where('regional_id', $rid)->orderBy('name')->get()
                : collect(),
        ]);
    }
}
