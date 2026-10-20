<?php

namespace App\Services\Agenda;

use App\Models\Event;
use Carbon\Carbon;

/**
 * Grelha semanal 07:00–21:00 para a vista Calendário.
 */
class AgendaWeekLayout
{
    /**
     * @return list<int>
     */
    public function hours(): array
    {
        return range(7, 21);
    }

    public function label(AgendaReadResult $result): string
    {
        if ($result->rangeStart === null || $result->rangeEnd === null) {
            return '';
        }

        $tz = config('app.timezone');
        $locale = app()->getLocale();
        $start = $result->rangeStart->copy()->timezone($tz)->locale($locale);
        $end = $result->rangeEnd->copy()->timezone($tz)->subDay()->locale($locale);

        if ($start->isSameMonth($end)) {
            return $start->translatedFormat('d').'–'.$end->translatedFormat('d M Y');
        }

        return $start->translatedFormat('d M').' – '.$end->translatedFormat('d M Y');
    }

    /**
     * @return list<array{date:string,label:string,isToday:bool}>
     */
    public function days(AgendaReadResult $result): array
    {
        if ($result->rangeStart === null || $result->rangeEnd === null) {
            return [];
        }

        $tz = config('app.timezone');
        $cursor = $result->rangeStart->copy()->timezone($tz)->startOfDay();
        $end = $result->rangeEnd->copy()->timezone($tz);
        $today = now($tz);
        $days = [];

        while ($cursor->lt($end)) {
            $days[] = [
                'date' => $cursor->toDateString(),
                'label' => $cursor->copy()->locale(app()->getLocale())->translatedFormat('D d/m'),
                'isToday' => $cursor->isSameDay($today),
            ];
            $cursor->addDay();
        }

        return $days;
    }

    /**
     * @return array<string, array{starts:list<array{eventId:int,editId:int,title:string,time:string,status:?string}>,continues:bool}>
     */
    public function blocks(AgendaReadResult $result): array
    {
        if ($result->rangeStart === null || $result->rangeEnd === null) {
            return [];
        }

        $tz = config('app.timezone');
        $rangeStart = $result->rangeStart->copy()->timezone($tz);
        $rangeEnd = $result->rangeEnd->copy()->timezone($tz);
        $cells = [];

        foreach ($result->entries() as $event) {
            $eventStart = $event->starts_at->copy()->timezone($tz);
            $eventEnd = ($event->ends_at ?? $event->starts_at)->copy()->timezone($tz);
            $cursor = $rangeStart->copy()->startOfDay();

            while ($cursor->lt($rangeEnd)) {
                $dayEnd = $cursor->copy()->addDay();
                if ($eventStart->lt($dayEnd) && $eventEnd->gt($cursor)) {
                    $visibleStart = $eventStart->greaterThan($cursor) ? $eventStart->copy() : $cursor->copy()->setTime(7, 0);
                    $visibleEnd = $eventEnd->lessThan($dayEnd) ? $eventEnd->copy() : $cursor->copy()->setTime(22, 0);
                    $open = $cursor->copy()->setTime(7, 0);
                    $close = $cursor->copy()->setTime(22, 0);
                    if ($visibleStart->lt($open)) {
                        $visibleStart = $open;
                    }
                    if ($visibleEnd->gt($close)) {
                        $visibleEnd = $close;
                    }
                    if ($visibleEnd->lte($visibleStart)) {
                        $visibleEnd = $visibleStart->copy()->addHour();
                    }

                    [$hour, $span] = $this->span($visibleStart, $visibleEnd);
                    $startKey = $cursor->toDateString().'|'.$hour;
                    $cells[$startKey]['starts'][] = [
                        'eventId' => (int) $event->id,
                        'editId' => $event->agendaEditId(),
                        'title' => (string) $event->title,
                        'time' => $eventStart->format('H:i'),
                        'status' => $this->statusLabel($event),
                    ];
                    $cells[$startKey]['continues'] = $cells[$startKey]['continues'] ?? false;
                    for ($next = $hour + 1; $next < $hour + $span && $next <= 21; $next++) {
                        $nextKey = $cursor->toDateString().'|'.$next;
                        $cells[$nextKey]['starts'] = $cells[$nextKey]['starts'] ?? [];
                        $cells[$nextKey]['continues'] = true;
                    }
                }
                $cursor = $dayEnd;
            }
        }

        return $cells;
    }

    /**
     * @return array{0:int,1:int}
     */
    protected function span(Carbon $start, Carbon $end): array
    {
        $hour = max(7, min(21, (int) $start->format('G')));
        $exclusive = ((int) $end->format('i') === 0 && (int) $end->format('s') === 0)
            ? (int) $end->format('G')
            : ((int) $end->format('G')) + 1;
        $exclusive = max($hour + 1, min(22, $exclusive));

        return [$hour, $exclusive - $hour];
    }

    protected function statusLabel(Event $event): ?string
    {
        return match ($event->effectiveStatus()) {
            'pending_approval' => 'Pendente',
            'draft' => 'Rascunho',
            default => null,
        };
    }
}
