<?php

namespace App\Livewire\Agenda;

use App\Livewire\Concerns\UsesAccountPagination;
use App\Models\WhatsAppNoticeTemplate;
use App\Services\OrganizationalContextService;
use App\Services\SlugService;
use Livewire\Component;
use Livewire\WithPagination;

class WhatsAppTemplateList extends Component
{
    use UsesAccountPagination;
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public string $search = '';

    public bool $show_form = false;

    public ?int $editing_id = null;

    public string $name = '';

    public string $body = '';

    public bool $is_active = true;

    public bool $is_default = false;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->authorize('create', WhatsAppNoticeTemplate::class);
        $this->resetForm();
        $this->show_form = true;
    }

    public function edit(int $id): void
    {
        $tpl = WhatsAppNoticeTemplate::query()->findOrFail($id);
        $this->authorize('update', $tpl);

        $this->editing_id = $tpl->id;
        $this->name = $tpl->name;
        $this->body = $tpl->body;
        $this->is_active = (bool) $tpl->is_active;
        $this->is_default = (bool) $tpl->is_default;
        $this->show_form = true;
    }

    public function save(SlugService $slugs): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:20000'],
            'is_active' => ['boolean'],
            'is_default' => ['boolean'],
        ]);

        $slug = $this->editing_id
            ? (WhatsAppNoticeTemplate::query()->find($this->editing_id)?->slug)
            : null;
        $slug = $slug ?? $slugs->generate($data['name'], 'whatsapp_notice_templates', [], $this->editing_id);

        if ($this->editing_id) {
            $tpl = WhatsAppNoticeTemplate::query()->findOrFail($this->editing_id);
            $this->authorize('update', $tpl);

            $tpl->update([
                'name' => $data['name'],
                'slug' => $slug,
                'body' => $data['body'],
                'is_active' => (bool) $data['is_active'],
                // is_default só pode ser alterado por moderador (gate próprio)
            ]);
        } else {
            $this->authorize('create', WhatsAppNoticeTemplate::class);

            $regionalId = app(OrganizationalContextService::class)->activeRegionalId()
                ?? auth()->user()?->organizationalRegionalId();

            WhatsAppNoticeTemplate::query()->create([
                'regional_id' => $regionalId,
                'name' => $data['name'],
                'slug' => $slug,
                'body' => $data['body'],
                'is_active' => (bool) $data['is_active'],
                'is_default' => false,
                'created_by' => auth()->id(),
            ]);
        }

        if (($data['is_default'] ?? false) && (auth()->user()?->isSuperAdmin() || auth()->user()?->can('moderar_conteudo'))) {
            $this->setDefault($this->editing_id);
        }

        session()->flash('status', __('Template guardado.'));
        $this->show_form = false;
        $this->resetForm();
    }

    public function setDefault(?int $id): void
    {
        $this->authorize('setDefault', WhatsAppNoticeTemplate::class);
        if (! $id) {
            return;
        }

        $tpl = WhatsAppNoticeTemplate::query()->findOrFail($id);
        // resetar defaults (escopo global simples)
        WhatsAppNoticeTemplate::query()->where('is_default', true)->update(['is_default' => false]);
        $tpl->update(['is_default' => true]);
    }

    public function resetForm(): void
    {
        $this->editing_id = null;
        $this->name = '';
        $this->body = '';
        $this->is_active = true;
        $this->is_default = false;
    }

    public function render(OrganizationalContextService $context)
    {
        $this->authorize('viewAny', WhatsAppNoticeTemplate::class);

        $user = auth()->user();
        $query = WhatsAppNoticeTemplate::query()->orderByDesc('is_default')->orderBy('name');

        if (! $user->isSuperAdmin()) {
            $ids = $user->accessibleRegionalIds();
            $query->where(function ($q) use ($ids) {
                $q->whereIn('regional_id', $ids)->orWhereNull('regional_id');
            });
        }

        $rid = $context->activeRegionalId();
        if ($rid) {
            $query->where(function ($q) use ($rid) {
                $q->where('regional_id', $rid)->orWhereNull('regional_id');
            });
        }

        if ($this->search !== '') {
            $s = '%'.$this->search.'%';
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', $s)->orWhere('slug', 'like', $s);
            });
        }

        return view('livewire.agenda.whatsapp-template-list', [
            'templates' => $query->paginate($this->paginatePerPage()),
        ]);
    }
}
