<?php

namespace Tests\Unit;

use App\Services\Agenda\BrazilianHolidayCatalog;
use PHPUnit\Framework\TestCase;

class BrazilianHolidayCatalogTest extends TestCase
{
    public function test_movable_dates_match_the_civil_calendar(): void
    {
        $catalog = new BrazilianHolidayCatalog;

        $this->assertSame('2024-03-31', $catalog->easterSunday(2024));
        $this->assertSame('2025-04-20', $catalog->easterSunday(2025));
        $this->assertSame('2026-04-05', $catalog->easterSunday(2026));
        $this->assertSame('2025-04-18', $catalog->goodFriday(2025));
        $this->assertSame('2024-04-08', $catalog->penha(2024));
        $this->assertSame('2025-04-28', $catalog->penha(2025));
    }

    public function test_national_and_state_holidays_follow_the_year_and_uf(): void
    {
        $catalog = new BrazilianHolidayCatalog;
        $sp = collect($catalog->forYear(2026, 'SP'))->keyBy('name');
        $rj = collect($catalog->forYear(2026, 'RJ'))->keyBy('name');
        $before = collect($catalog->forYear(2023, 'SP'))->keyBy('name');

        $this->assertSame('2026-01-01', $sp['Confraternização Universal']['observed_on']);
        $this->assertSame('2026-04-03', $sp['Paixão de Cristo']['observed_on']);
        $this->assertSame('2026-11-20', $sp['Consciência Negra']['observed_on']);
        $this->assertSame('2026-07-09', $sp['Revolução Constitucionalista']['observed_on']);
        $this->assertSame('state', $sp['Revolução Constitucionalista']['scope']);
        $this->assertSame('SP', $sp['Revolução Constitucionalista']['state']);
        $this->assertArrayNotHasKey('São Jorge', $sp);
        $this->assertSame('2026-04-23', $rj['São Jorge']['observed_on']);
        $this->assertArrayNotHasKey('Consciência Negra', $before);
        $this->assertArrayNotHasKey('Revolução Constitucionalista', collect($catalog->forYear(2026))->keyBy('name'));
    }
}
