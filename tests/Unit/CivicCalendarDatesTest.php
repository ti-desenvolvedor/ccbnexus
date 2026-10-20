<?php

namespace Tests\Unit;

use App\Services\Agenda\CivicCalendarDates;
use PHPUnit\Framework\TestCase;

class CivicCalendarDatesTest extends TestCase
{
    public function test_2026_has_enem_and_general_elections(): void
    {
        $dates = collect((new CivicCalendarDates)->forYear(2026))->keyBy('source_key');

        $this->assertSame('2026-11-08', $dates['event:enem:2026-11-08']['observed_on']);
        $this->assertSame('2026-11-15', $dates['event:enem:2026-11-15']['observed_on']);
        $this->assertSame('2026-10-04', $dates['event:election:2026-10-04']['observed_on']);
        $this->assertSame('2026-10-25', $dates['event:election:2026-10-25']['observed_on']);
        $this->assertSame('event', $dates['event:enem:2026-11-08']['scope']);
    }

    public function test_a_year_without_an_enem_edital_only_skips_enem(): void
    {
        $names = array_column((new CivicCalendarDates)->forYear(2027), 'name');

        $this->assertNotContains('ENEM — 1º dia', $names);
        $this->assertSame([], array_filter($names, fn (string $name): bool => str_contains($name, 'Eleições')));
    }
}
