<?php

namespace App\Services\Recurrence;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Repete na mesma data/hora do calendário a cada N anos (ex.: anual, bienal).
 */
class YearlyRecurrencePlanner
{
    /**
     * @param  array{interval_years?:int}  $config
     * @return array<int, Carbon> Ocorrências estritamente posteriores a $parentStart e até $untilEnd.
     */
    public function occurrenceStarts(
        CarbonInterface $parentStart,
        CarbonInterface $untilEnd,
        array $config,
        int $maxOccurrences = 100,
    ): array {
        $intervalYears = max(1, min(50, (int) ($config['interval_years'] ?? 1)));
        $parent = Carbon::parse($parentStart);
        $until = Carbon::parse($untilEnd)->endOfDay();

        $out = [];
        $cursor = $parent->copy()->addYears($intervalYears);

        while ($cursor->lte($until) && count($out) < $maxOccurrences) {
            $out[] = $cursor->copy();
            $cursor->addYears($intervalYears);
        }

        return $out;
    }

    /**
     * @param  array{interval_years?:int}  $config
     */
    public function countOccurrences(
        CarbonInterface $parentStart,
        CarbonInterface $untilEnd,
        array $config,
        int $maxOccurrences = 100,
    ): int {
        return count($this->occurrenceStarts($parentStart, $untilEnd, $config, $maxOccurrences));
    }
}
