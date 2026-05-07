<?php

namespace Tests\Unit;

use App\Services\Recurrence\MonthlyNthRecurrencePlanner;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class MonthlyNthRecurrencePlannerTest extends TestCase
{
    public function test_second_friday_even_months_2026(): void
    {
        $planner = new MonthlyNthRecurrencePlanner;
        $parent = Carbon::parse('2026-01-10 09:00:00');
        $until = Carbon::parse('2026-12-31 23:59:59');
        $config = [
            'nth' => 2,
            'weekday' => 5,
            'months' => ['type' => MonthlyNthRecurrencePlanner::MONTH_FILTER_EVEN, 'list' => []],
        ];

        $dates = $planner->occurrenceStarts($parent, $until, $config, 52);

        $this->assertNotEmpty($dates);
        foreach ($dates as $d) {
            $this->assertSame(5, (int) $d->format('w'), 'Deve ser sexta-feira');
            $this->assertSame(0, $d->minute);
            $this->assertSame(9, $d->hour);
            $this->assertSame(0, $d->month % 2, 'Mês deve ser par');
        }
    }

    public function test_second_friday_march_july_september(): void
    {
        $planner = new MonthlyNthRecurrencePlanner;
        $parent = Carbon::parse('2026-01-01 14:00:00');
        $until = Carbon::parse('2026-12-31');
        $config = [
            'nth' => 2,
            'weekday' => 5,
            'months' => ['type' => MonthlyNthRecurrencePlanner::MONTH_FILTER_LIST, 'list' => [3, 7, 9]],
        ];

        $dates = $planner->occurrenceStarts($parent, $until, $config, 52);
        $months = array_map(fn (Carbon $d) => (int) $d->month, $dates);

        $this->assertContains(3, $months);
        $this->assertContains(7, $months);
        $this->assertContains(9, $months);
        foreach ($months as $m) {
            $this->assertContains($m, [3, 7, 9]);
        }
    }

    public function test_legacy_month_type_even_still_works(): void
    {
        $planner = new MonthlyNthRecurrencePlanner;
        $this->assertTrue($planner->monthMatchesFilter(2, ['type' => 'even', 'list' => []]));
        $this->assertFalse($planner->monthMatchesFilter(3, ['type' => 'even', 'list' => []]));
    }

    public function test_union_even_and_list_includes_odd_listed_month(): void
    {
        $planner = new MonthlyNthRecurrencePlanner;
        $this->assertTrue($planner->monthMatchesFilter(3, [
            'modes' => ['even', 'list'],
            'list' => [3],
        ]));
        $this->assertTrue($planner->monthMatchesFilter(4, [
            'modes' => ['even', 'list'],
            'list' => [3],
        ]));
        $this->assertFalse($planner->monthMatchesFilter(5, [
            'modes' => ['even', 'list'],
            'list' => [3],
        ]));
    }
}
