<?php

namespace App\Services;

use App\Models\Event;
use App\Models\User;
use App\Models\WhatsAppNotice;
use App\Models\WhatsAppNoticeTemplate;
use Illuminate\Support\Facades\DB;

class WhatsAppNoticeService
{
    public function __construct(
        protected WhatsAppNoticeRenderer $renderer,
    ) {}

    /**
     * @param  array<string, string|null>  $overrides
     */
    public function createDraftFromEvent(Event $event, WhatsAppNoticeTemplate $template, User $user, array $overrides = []): WhatsAppNotice
    {
        return DB::transaction(function () use ($event, $template, $overrides) {
            $event->loadMissing('whatsappGroups');
            $body = $this->renderer->renderForEvent($event, $template, $overrides);
            $routing = $this->routingFooter($event);
            if ($routing !== '' && ! str_contains($body, $routing)) {
                $body = trim($body)."\n\n".$routing;
            }

            return WhatsAppNotice::query()->create([
                'event_id' => $event->id,
                'template_id' => $template->id,
                'regional_id' => $event->regional_id,
                'title' => $event->title,
                'body_final' => $body,
                'status' => WhatsAppNotice::STATUS_DRAFT,
            ]);
        });
    }

    /**
     * Grupos WhatsApp alvo do evento (roteamento manual).
     *
     * @return list<string>
     */
    public function targetGroupNames(Event $event): array
    {
        $event->loadMissing('whatsappGroups');

        return $event->whatsappGroups
            ->where('is_active', true)
            ->pluck('name')
            ->filter()
            ->values()
            ->all();
    }

    protected function routingFooter(Event $event): string
    {
        $names = $this->targetGroupNames($event);
        if ($names === []) {
            return '';
        }

        return '[Enviar para: '.implode(', ', $names).']';
    }

    public function updateDraftText(WhatsAppNotice $notice, string $bodyFinal): WhatsAppNotice
    {
        $notice->update([
            'body_final' => $bodyFinal,
        ]);

        return $notice->fresh();
    }

    public function markSentManual(WhatsAppNotice $notice, User $user): WhatsAppNotice
    {
        $notice->update([
            'status' => WhatsAppNotice::STATUS_SENT_MANUAL,
            'sent_at' => now(),
            'sent_by' => $user->id,
        ]);

        return $notice->fresh();
    }
}
