<?php

namespace App\Livewire\Agenda;

use App\Models\Event;
use App\Models\WhatsAppNotice;
use App\Models\WhatsAppNoticeTemplate;
use App\Services\OrganizationalContextService;
use App\Services\WhatsAppNoticeService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class WhatsAppNotificationsCenter extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public string $search = '';

    public ?int $selected_event_id = null;
    public ?int $selected_template_id = null;

    public bool $show_editor = false;
    public string $body_final = '';

    public ?int $active_notice_id = null;

    /** @var array<int, string> */
    public array $flash_copies = [];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openEditor(int $eventId): void
    {
        $event = Event::query()->with(['whatsappNoticeTemplate', 'location', 'eventType'])->findOrFail($eventId);
        $this->authorize('update', $event);

        if (! $event->whatsapp_enabled) {
            $this->addError('selected_event_id', __('WhatsApp não está ativo neste evento.'));

            return;
        }

        $this->selected_event_id = $event->id;
        $this->selected_template_id = $event->whatsapp_notice_template_id;
        $this->active_notice_id = null;
        $this->body_final = '';
        $this->show_editor = true;
    }

    public function generate(WhatsAppNoticeService $service): void
    {
        $eventId = (int) ($this->selected_event_id ?? 0);
        $tplId = (int) ($this->selected_template_id ?? 0);
        if (! $eventId || ! $tplId) {
            $this->addError('selected_template_id', __('Selecione um template.'));

            return;
        }

        $event = Event::query()->with(['location', 'eventType'])->findOrFail($eventId);
        $this->authorize('update', $event);

        $template = WhatsAppNoticeTemplate::query()->where('is_active', true)->findOrFail($tplId);

        $this->authorize('createFromEvent', [WhatsAppNotice::class, $event]);

        $notice = $service->createDraftFromEvent($event, $template, auth()->user(), [
            'signature' => $event->regional?->name ? ('A Administração'."\n".$event->regional->name) : '',
        ]);

        $this->active_notice_id = $notice->id;
        $this->body_final = $notice->body_final;
    }

    public function saveDraft(WhatsAppNoticeService $service): void
    {
        if (! $this->active_notice_id) {
            return;
        }
        $notice = WhatsAppNotice::query()->findOrFail($this->active_notice_id);
        $this->authorize('view', $notice);

        $service->updateDraftText($notice, $this->body_final);
        session()->flash('status', __('Rascunho atualizado.'));
    }

    public function markSent(WhatsAppNoticeService $service): void
    {
        if (! $this->active_notice_id) {
            return;
        }
        $notice = WhatsAppNotice::query()->findOrFail($this->active_notice_id);
        $this->authorize('markSent', $notice);

        $service->markSentManual($notice, auth()->user());
        session()->flash('status', __('Marcado como enviado (manual).'));
    }

    public function getWaLinkProperty(): ?string
    {
        if (trim($this->body_final) === '') {
            return null;
        }

        return 'https://wa.me/?text='.rawurlencode($this->body_final);
    }

    public function render(OrganizationalContextService $context)
    {
        $this->authorize('viewAny', WhatsAppNotice::class);

        $user = auth()->user();

        $query = Event::query()
            ->with(['regional', 'eventType'])
            ->where('is_occurrence', false)
            ->whereNull('parent_event_id')
            ->where('whatsapp_enabled', true)
            ->orderByDesc('starts_at');

        if (! $user->isSuperAdmin()) {
            $ids = $user->accessibleRegionalIds();
            $query->where(function ($q) use ($ids) {
                $q->whereIn('regional_id', $ids)->orWhereNull('regional_id');
            });
        }

        $rid = $context->activeRegionalId();
        if ($rid) {
            $query->where('regional_id', $rid);
        }

        if ($this->search !== '') {
            $s = '%'.$this->search.'%';
            $query->where('title', 'like', $s);
        }

        $templates = WhatsAppNoticeTemplate::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        $history = [];
        if ($this->selected_event_id) {
            $history = WhatsAppNotice::query()
                ->where('event_id', $this->selected_event_id)
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();
        }

        return view('livewire.agenda.whatsapp-notifications-center', [
            'events' => $query->paginate(12),
            'templates' => $templates,
            'history' => $history,
        ]);
    }
}

