<?php

namespace App\Services\Recurrence;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Planeia ocorrências: «énésimo dia da semana do mês» com filtro de meses.
 *
 * Ex.: 2.ª sexta em meses pares; 2.ª sexta em março, julho e setembro.
 */
class MonthlyNthRecurrencePlanner
{
    public const MONTH_FILTER_ALL = 'all';

    public const MONTH_FILTER_EVEN = 'even';

    public const MONTH_FILTER_ODD = 'odd';

    public const MONTH_FILTER_LIST = 'list';

    /**
     * @param  array{nth:int,weekday:int,months:array{type?:string,modes?:array<int|string>,list?:array<int>}}  $config
     * @return array<int, Carbon> Inícios de ocorrência (mesma hora que o pai), estritamente posteriores a $parentStart e até $untilEnd.
     */
    public function occurrenceStarts(
        CarbonInterface $parentStart,
        CarbonInterface $untilEnd,
        array $config,
        int $maxOccurrences = 52,
    ): array {
        $nth = (int) ($config['nth'] ?? 1);
        $weekday = (int) ($config['weekday'] ?? 0);
        $months = $config['months'] ?? ['type' => self::MONTH_FILTER_ALL, 'list' => []];

        $parent = Carbon::parse($parentStart);
        $until = Carbon::parse($untilEnd)->endOfDay();

        $cursor = $parent->copy()->startOfMonth();
        $limitCursor = $until->copy()->addMonth()->startOfMonth();

        $out = [];
        $guard = 0;

        while ($cursor->lte($limitCursor) && count($out) < $maxOccurrences && $guard < 600) {
            $guard++;
            $y = (int) $cursor->year;
            $m = (int) $cursor->month;

            if ($this->monthMatchesFilter($m, $months)) {
                $day = $this->nthWeekdayOfMonth($y, $m, $weekday, $nth);
                if ($day !== null) {
                    $occStart = $day->copy()->setTimeFromTimeString($parent->toTimeString());
                    if ($occStart->gt($parent) && $occStart->lte($until)) {
                        $out[] = $occStart->copy();
                    }
                }
            }

            $cursor->addMonthNoOverflow();
        }

        usort($out, fn (Carbon $a, Carbon $b): int => $a->timestamp <=> $b->timestamp);

        return array_slice(array_values($out), 0, $maxOccurrences);
    }

    /**
     * @param  array{type?:string,modes?:array<int|string>,list?:array<int>}  $months
     */
    public function monthMatchesFilter(int $month, array $months): bool
    {
        if (isset($months['type'])) {
            $type = (string) $months['type'];

            return match ($type) {
                self::MONTH_FILTER_ALL => true,
                self::MONTH_FILTER_EVEN => $month % 2 === 0,
                self::MONTH_FILTER_ODD => $month % 2 === 1,
                self::MONTH_FILTER_LIST => in_array($month, array_map('intval', $months['list'] ?? []), true),
                default => true,
            };
        }

        if (isset($months['modes']) && is_array($months['modes'])) {
            $modes = array_values(array_unique(array_map(static fn ($m): string => (string) $m, $months['modes'])));
            if ($modes === []) {
                return false;
            }
            if (in_array(self::MONTH_FILTER_ALL, $modes, true)) {
                return true;
            }
            $list = array_map('intval', $months['list'] ?? []);
            foreach ($modes as $mode) {
                if ($mode === self::MONTH_FILTER_EVEN && $month % 2 === 0) {
                    return true;
                }
                if ($mode === self::MONTH_FILTER_ODD && $month % 2 === 1) {
                    return true;
                }
                if ($mode === self::MONTH_FILTER_LIST && in_array($month, $list, true)) {
                    return true;
                }
            }

            return false;
        }

        return true;
    }

    /**
     * @param  int  $nth  1–5 (1.ª … 5.ª) ou -1 (última do mês)
     */
    public function nthWeekdayOfMonth(int $year, int $month, int $weekday, int $nth): ?Carbon
    {
        $weekday = max(0, min(6, $weekday));

        $first = Carbon::createFromDate($year, $month, 1)->startOfDay();

        if ($nth === -1) {
            $last = $first->copy()->endOfMonth();
            $dowLast = (int) $last->format('w');
            $delta = ($dowLast - $weekday + 7) % 7;

            return $last->copy()->subDays($delta)->startOfDay();
        }

        if ($nth < 1 || $nth > 5) {
            return null;
        }

        $carbonWeekday = $this->toCarbonWeekdayConstant($weekday);
        if ($carbonWeekday === null) {
            return null;
        }

        $candidate = $first->copy()->nthOfMonth($nth, $carbonWeekday);
        if ($candidate === false) {
            return null;
        }

        if ((int) $candidate->month !== $month) {
            return null;
        }

        return $candidate->startOfDay();
    }

    public function countOccurrences(
        CarbonInterface $parentStart,
        CarbonInterface $untilEnd,
        array $config,
        int $maxOccurrences = 52,
    ): int {
        return count($this->occurrenceStarts($parentStart, $untilEnd, $config, $maxOccurrences));
    }

    private function toCarbonWeekdayConstant(int $weekday0to6): ?int
    {
        return match ($weekday0to6) {
            0 => Carbon::SUNDAY,
            1 => Carbon::MONDAY,
            2 => Carbon::TUESDAY,
            3 => Carbon::WEDNESDAY,
            4 => Carbon::THURSDAY,
            5 => Carbon::FRIDAY,
            6 => Carbon::SATURDAY,
            default => null,
        };
    }
}
