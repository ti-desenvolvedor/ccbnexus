<?php

namespace Tests\Unit;

use App\Services\Recurrence\YearlyRecurrencePlanner;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class YearlyRecurrencePlannerTest extends TestCase
{
    public function test_every_two_years(): void
    {
        $planner = new YearlyRecurrencePlanner;
        $parent = Carbon::parse('2026-06-10 14:30:00');
        $until = Carbon::parse('2032-12-31');
        $config = ['interval_years' => 2];

        $dates = $planner->occurrenceStarts($parent, $until, $config, 100);

        $this->assertCount(3, $dates);
        $this->assertSame('2028-06-10', $dates[0]->format('Y-m-d'));
        $this->assertSame('2030-06-10', $dates[1]->format('Y-m-d'));
        $this->assertSame('2032-06-10', $dates[2]->format('Y-m-d'));
        foreach ($dates as $d) {
            $this->assertSame('14:30:00', $d->format('H:i:s'));
        }
    }

    public function test_annual_count(): void
    {
        $planner = new YearlyRecurrencePlanner;
        $parent = Carbon::parse('2025-01-01');
        $until = Carbon::parse('2029-06-15');
        $n = $planner->countOccurrences($parent, $until, ['interval_years' => 1], 100);

        $this->assertSame(4, $n);
    }
}
