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
        return DB::transaction(function () use ($event, $template, $user, $overrides) {
            $body = $this->renderer->renderForEvent($event, $template, $overrides);

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

