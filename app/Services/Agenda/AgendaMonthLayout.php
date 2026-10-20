<?php

namespace App\Services\Agenda;

use App\Models\Event;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Monta a grelha mensal e a agenda dia a dia a partir do mesmo AgendaReadResult.
 */
class AgendaMonthLayout
{
    public const MAX_CHIPS = 3;

    /**
     * @return list<list<array{date:string,inMonth:bool,day:int,events:list<array{time:string,title:string,status:?string}>,more:int}>>
     */
    public function grid(AgendaReadResult $result): array
    {
        $start = $result->rangeStart?->copy()->timezone(config('app.timezone'))->startOfMonth();
        $end = $result->rangeEnd?->copy()->timezone(config('app.timezone'));
        if ($start === null || $end === null) {
            return [];
        }

        $byDay = $this->eventsByDay($result, $start, $end);
        $cursor = $start->copy()->startOfWeek();
        $gridEnd = $end->copy()->subDay()->endOfWeek()->addDay()->startOfDay();
        $weeks = [];
        $week = [];

        while ($cursor->lt($gridEnd)) {
            $key = $cursor->toDateString();
            $events = $byDay[$key] ?? [];
            $week[] = [
                'date' => $key,
                'inMonth' => $cursor->month === $start->month && $cursor->year === $start->year,
                'isToday' => $cursor->isSameDay(now(config('app.timezone'))),
                'day' => $cursor->day,
                'events' => array_slice($events, 0, self::MAX_CHIPS),
                'more' => max(0, count($events) - self::MAX_CHIPS),
            ];
            $cursor->addDay();
            if (count($week) === 7) {
                $weeks[] = $week;
                $week = [];
            }
        }

        return $weeks;
    }

    /**
     * @return list<array{heading:string,events:list<array{time:string,title:string,type:string,place:string,attendant:string,channels:list<string>,status:?string}>}>
     */
    public function agenda(AgendaReadResult $result): array
    {
        $start = $result->rangeStart?->copy()->timezone(config('app.timezone'))->startOfMonth();
        $end = $result->rangeEnd?->copy()->timezone(config('app.timezone'));
        if ($start === null || $end === null) {
            return [];
        }

        $days = [];
        foreach ($result->entries() as $event) {
            foreach ($this->overlappingDays($event, $start, $end) as $day) {
                $key = $day->toDateString();
                $days[$key][] = $this->agendaRow($event);
            }
        }

        ksort($days);
        $blocks = [];
        foreach ($days as $key => $events) {
            $day = Carbon::parse($key, config('app.timezone'));
            $blocks[] = [
                'date' => $key,
                'heading' => $this->heading($day),
                'events' => $events,
            ];
        }

        return $blocks;
    }

    public function monthLabel(AgendaReadResult $result): string
    {
        $start = $result->rangeStart?->copy()->timezone(config('app.timezone'))->locale(app()->getLocale());

        return $start ? $start->translatedFormat('F Y') : '';
    }

    /**
     * @return array<string, list<array{time:string,title:string,status:?string}>>
     */
    protected function eventsByDay(AgendaReadResult $result, CarbonInterface $start, CarbonInterface $end): array
    {
        $byDay = [];
        foreach ($result->entries() as $event) {
            foreach ($this->overlappingDays($event, $start, $end) as $day) {
                $byDay[$day->toDateString()][] = [
                    'eventId' => (int) $event->id,
                    'editId' => $event->agendaEditId(),
                    'time' => $event->starts_at->timezone(config('app.timezone'))->format('H:i'),
                    'title' => (string) $event->title,
                    'status' => $this->statusLabel($event),
                ];
            }
        }

        return $byDay;
    }

    /**
     * @return list<Carbon>
     */
    protected function overlappingDays(Event $event, CarbonInterface $rangeStart, CarbonInterface $rangeEnd): array
    {
        $tz = config('app.timezone');
        $eventStart = $event->starts_at->copy()->timezone($tz);
        $eventEnd = ($event->ends_at ?? $event->starts_at)->copy()->timezone($tz);
        $cursor = $rangeStart->copy()->timezone($tz)->startOfDay();
        $days = [];

        while ($cursor->lt($rangeEnd)) {
            $dayEnd = $cursor->copy()->addDay();
            if ($eventStart->lt($dayEnd) && $eventEnd->gt($cursor)) {
                $days[] = $cursor->copy();
            }
            $cursor = $dayEnd;
        }

        return $days;
    }

    /**
     * @return array{time:string,title:string,type:string,place:string,attendant:string,channels:list<string>,status:?string}
     */
    protected function agendaRow(Event $event): array
    {
        $tz = config('app.timezone');
        $start = $event->starts_at->timezone($tz)->format('H:i');
        $end = ($event->ends_at ?? $event->starts_at)->timezone($tz)->format('H:i');
        $channels = [];
        if ($event->include_on_baptism_list) {
            $channels[] = 'Boletim';
        }
        if ($event->whatsapp_enabled) {
            $channels[] = 'WA';
        }

        return [
            'eventId' => (int) $event->id,
            'editId' => $event->agendaEditId(),
            'time' => $start.'–'.$end,
            'title' => (string) $event->title,
            'type' => $event->eventType?->name ?? '—',
            'place' => $event->meetingRoom?->name ?? $event->location?->name ?? '—',
            'attendant' => $event->attendantLabel() ?: '—',
            'channels' => $channels,
            'status' => $this->statusLabel($event),
        ];
    }

    protected function statusLabel(Event $event): ?string
    {
        return match ($event->effectiveStatus()) {
            'pending_approval' => 'Pendente',
            'draft' => 'Rascunho',
            default => null,
        };
    }

    protected function heading(CarbonInterface $day): string
    {
        $names = [
            Carbon::SUNDAY => 'domingo',
            Carbon::MONDAY => 'segunda',
            Carbon::TUESDAY => 'terça',
            Carbon::WEDNESDAY => 'quarta',
            Carbon::THURSDAY => 'quinta',
            Carbon::FRIDAY => 'sexta',
            Carbon::SATURDAY => 'sábado',
        ];
        $month = $day->copy()->locale(app()->getLocale())->translatedFormat('F');

        return $names[$day->dayOfWeek].', '.$day->day.' de '.$month;
    }
}
