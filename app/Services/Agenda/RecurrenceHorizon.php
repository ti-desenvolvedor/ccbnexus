<?php

namespace App\Services\Agenda;

use App\Models\Event;
use App\Services\EventRecurrenceService;
use Carbon\Carbon;

/**
 * Leva séries anuais (fim em 31/12) até 31/12 do ano seguinte
 * e cria só as ocorrências que ainda não existem.
 */
class RecurrenceHorizon
{
    public function __construct(
        protected EventRecurrenceService $recurrence,
    ) {}

    /**
     * @return array{series: int, dates: int, through: string}
     */
    public function extendOpenSeries(?int $regionalId = null): array
    {
        $currentYear = (int) now()->year;
        $nextYear = $currentYear + 1;
        $horizon = Carbon::create($nextYear, 12, 31)->startOfDay();
        $from = Carbon::create($currentYear, 1, 1)->toDateString();

        $query = Event::query()
            ->where('is_occurrence', false)
            ->whereNull('parent_event_id')
            ->whereNotNull('recurrence_frequency')
            ->where('recurrence_frequency', '!=', '')
            ->whereNotNull('recurrence_until')
            ->whereDate('recurrence_until', '>=', $from);

        if ($regionalId !== null) {
            $query->where('regional_id', $regionalId);
        }

        $series = 0;
        $dates = 0;

        foreach ($query->get() as $event) {
            $until = Carbon::parse($event->recurrence_until)->startOfDay();
            $extended = false;

            if ($until->month === 12 && $until->day === 31 && $until->lt($horizon)) {
                $event->recurrence_until = $horizon->toDateString();
                $event->save();
                $extended = true;
                $event = $event->fresh() ?? $event;
            }

            $added = $this->recurrence->ensureOccurrences($event);
            if ($extended || $added > 0) {
                $series++;
                $dates += $added;
            }
        }

        return [
            'series' => $series,
            'dates' => $dates,
            'through' => $horizon->toDateString(),
        ];
    }
}
