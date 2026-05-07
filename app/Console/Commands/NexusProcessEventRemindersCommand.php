<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\EventNotificationDispatch;
use App\Models\NotificationRuleTemplate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class NexusProcessEventRemindersCommand extends Command
{
    protected $signature = 'nexus:event-reminders';

    protected $description = 'Dispara lembretes de eventos conforme templates (30/15/7/1 dias) — canal log em desenvolvimento.';

    public function handle(): int
    {
        $rules = NotificationRuleTemplate::query()->where('is_active', true)->orderBy('days_before')->get();
        $today = now()->startOfDay();

        foreach ($rules as $rule) {
            $target = $today->copy()->addDays((int) $rule->days_before);

            Event::query()
                ->where('status', 'published')
                ->whereDate('starts_at', $target->toDateString())
                ->chunk(100, function ($events) use ($rule, $today) {
                    foreach ($events as $event) {
                        $scheduledFor = $today->copy()->setTimeFromTimeString('08:00:00');
                        $exists = EventNotificationDispatch::query()
                            ->where('event_id', $event->id)
                            ->where('days_before', $rule->days_before)
                            ->whereDate('scheduled_for', $scheduledFor->toDateString())
                            ->exists();
                        if ($exists) {
                            continue;
                        }
                        EventNotificationDispatch::query()->create([
                            'event_id' => $event->id,
                            'days_before' => $rule->days_before,
                            'scheduled_for' => $scheduledFor,
                            'sent_at' => now(),
                            'channel' => 'log',
                            'payload' => json_encode([
                                'rule' => $rule->name,
                                'event' => $event->title,
                            ]),
                        ]);
                        Log::info('Nexus event reminder', [
                            'event_id' => $event->id,
                            'days_before' => $rule->days_before,
                            'rule' => $rule->name,
                        ]);
                    }
                });
        }

        $this->info('Processamento concluído.');

        return self::SUCCESS;
    }
}
