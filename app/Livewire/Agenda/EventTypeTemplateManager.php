<?php

namespace App\Livewire\Agenda;

use App\Models\Event;
use App\Models\EventType;
use App\Models\EventTypeAudiencePreset;
use App\Models\Location;
use App\Models\Regional;
use App\Services\EventTypeAudiencePresetService;
use App\Support\SimpleRichText;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class EventTypeTemplateManager extends Component
{
    use AuthorizesRequests;

    public string $search = '';

    public ?int $editing_id = null;

    public bool $creating = false;

    public string $name = '';

    public string $paragraph = '';

    public string $report_layout = 'list_only';

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->can('gerenciar_avisos')), 403);
    }

    public function edit(int $eventTypeId): void
    {
        $type = EventType::query()->with('audiencePreset')->findOrFail($eventTypeId);
        $this->creating = false;
        $this->editing_id = $type->id;
        $this->name = $type->name;
        $this->paragraph = (string) ($type->audiencePreset?->invitation_paragraph ?? '');
        $this->report_layout = (string) ($type->audiencePreset?->report_layout ?: 'list_only');
        $this->dispatch('simple-editor-load', target: 'paragraph', html: $this->paragraph);
    }

    public function startCreate(): void
    {
        $this->creating = true;
        $this->editing_id = null;
        $this->name = '';
        $this->paragraph = '';
        $this->report_layout = 'narrative';
        $this->resetValidation();
        $this->dispatch('simple-editor-load', target: 'paragraph', html: '');
    }

    public function save(EventTypeAudiencePresetService $presets): void
    {
        $user = auth()->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->can('gerenciar_avisos')), 403);

        $clean = SimpleRichText::sanitize($this->paragraph);
        if ($clean !== $this->paragraph) {
            $this->paragraph = $clean;
            $this->dispatch('simple-editor-load', target: 'paragraph', html: $clean);
        }

        $data = $this->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('event_types', 'name')->ignore($this->editing_id),
            ],
            'paragraph' => ['nullable', 'string', 'max:20000'],
            'report_layout' => ['required', 'in:list_only,narrative,intro_list'],
        ], [
            'name.required' => 'Informe o nome do tipo.',
            'name.unique' => 'Já existe um tipo com este nome.',
        ]);

        if ($this->creating || ! $this->editing_id) {
            $type = EventType::query()->create([
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($data['name']),
                'is_active' => true,
                'sort_order' => ((int) EventType::query()->max('sort_order')) + 10,
                'meta' => [
                    'default_include_on_baptism_list' => false,
                    'default_whatsapp_enabled' => true,
                    'report_section' => null,
                ],
            ]);
            $this->creating = false;
            $this->editing_id = $type->id;
        } else {
            $type = EventType::query()->findOrFail($this->editing_id);
            $type->update(['name' => $data['name']]);
        }

        $presets->saveParagraph($type->id, (string) $data['paragraph'], $data['report_layout']);
        session()->flash('status', 'Tipo guardado. A lista de batismos usa este texto na próxima geração.');
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'tipo';
        }

        $slug = $base;
        $suffix = 2;
        while (EventType::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    public function render(EventTypeAudiencePresetService $presets)
    {
        $types = EventType::query()
            ->with('audiencePreset')
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)->orWhere('slug', 'like', $term);
                });
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $preview = '';
        if ($this->editing_id && trim($this->paragraph) !== '') {
            $preset = new EventTypeAudiencePreset(['invitation_paragraph' => $this->paragraph]);
            $draft = new Event([
                'title' => 'Reunião de exemplo',
                'starts_at' => now()->locale('pt_BR')->setTime(19, 30),
                'ends_at' => now()->locale('pt_BR')->setTime(21, 0),
            ]);
            $draft->setRelation('regional', new Regional(['name' => 'Regional Embu das Artes']));
            $draft->setRelation('location', new Location(['name' => 'Embu das Artes - Central']));
            $preview = $presets->renderParagraph($preset, $draft);
        }

        return view('livewire.agenda.event-type-template-manager', [
            'types' => $types,
            'preview' => $preview,
            'fields' => $presets->placeholderCatalog(),
            'layouts' => [
                'list_only' => 'Tabela (batismo, santa ceia, mocidade, ensaio)',
                'narrative' => 'Parágrafo (reuniões regionais)',
                'intro_list' => 'Introdução e lista (RCO, RMA)',
            ],
        ]);
    }
}
