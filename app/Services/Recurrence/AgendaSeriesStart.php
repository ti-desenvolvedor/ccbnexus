<?php

namespace App\Services\Recurrence;

use Carbon\Carbon;
use InvalidArgumentException;

/**
 * Primeira ocorrência de uma série «énésimo dia da semana» dentro de um ano.
 * A data não é digitada: sai de nth + dia da semana + meses.
 */
class AgendaSeriesStart
{
    public function __construct(
        protected MonthlyNthRecurrencePlanner $planner,
    ) {}

    /**
     * @param  array{nth:int,weekday:int,months?:array<string, mixed>}  $recurrence
     */
    public function firstInYear(int $year, string $time, array $recurrence): Carbon
    {
        $nth = (int) ($recurrence['nth'] ?? 0);
        $weekday = (int) ($recurrence['weekday'] ?? 0);
        $months = $recurrence['months'] ?? ['type' => MonthlyNthRecurrencePlanner::MONTH_FILTER_ALL];

        for ($month = 1; $month <= 12; $month++) {
            if (! $this->planner->monthMatchesFilter($month, $months)) {
                continue;
            }

            $day = $this->planner->nthWeekdayOfMonth($year, $month, $weekday, $nth);
            if ($day === null) {
                continue;
            }

            return $day->setTimeFromTimeString($this->normalizeTime($time));
        }

        throw new InvalidArgumentException("Nenhuma ocorrência em {$year} para a regra informada.");
    }

    private function normalizeTime(string $time): string
    {
        $time = trim($time);
        if (preg_match('/^\d{1,2}:\d{2}$/', $time) === 1) {
            return $time.':00';
        }

        return $time;
    }
}
