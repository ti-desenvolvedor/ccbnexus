<?php

namespace App\Services\Agenda;

use DateTimeImmutable;

/**
 * Datas nacionais que não são feriado: Enem (só anos com edital conhecido)
 * e eleições (1º domingo e último domingo de outubro, lei eleitoral).
 */
class CivicCalendarDates
{
    /** @var array<int, array{0: string, 1: string}> */
    private const ENEM = [
        2026 => ['2026-11-08', '2026-11-15'],
    ];

    /**
     * @return list<array{observed_on: string, name: string, scope: string, state: null, source_key: string}>
     */
    public function forYear(int $year): array
    {
        $rows = [];
        if (isset(self::ENEM[$year])) {
            [$first, $second] = self::ENEM[$year];
            $rows[] = $this->row($first, 'ENEM — 1º dia', 'event:enem:'.$first);
            $rows[] = $this->row($second, 'ENEM — 2º dia', 'event:enem:'.$second);
        }

        if ($year % 4 === 2 || $year % 4 === 0) {
            $kind = $year % 4 === 2 ? 'Eleições gerais' : 'Eleições municipais';
            $first = $this->sundayOfOctober($year, true);
            $second = $this->sundayOfOctober($year, false);
            $rows[] = $this->row($first, $kind.' — 1º turno', 'event:election:'.$first);
            $rows[] = $this->row($second, $kind.' — 2º turno', 'event:election:'.$second);
        }

        return $rows;
    }

    public function sundayOfOctober(int $year, bool $first): string
    {
        if ($first) {
            $day = new DateTimeImmutable(sprintf('%04d-10-01', $year));
            $shift = (7 - (int) $day->format('w')) % 7;

            return $day->modify('+'.$shift.' days')->format('Y-m-d');
        }

        $day = new DateTimeImmutable(sprintf('%04d-10-31', $year));
        $shift = (int) $day->format('w');

        return $day->modify('-'.$shift.' days')->format('Y-m-d');
    }

    /**
     * @return array{observed_on: string, name: string, scope: string, state: null, source_key: string}
     */
    private function row(string $date, string $name, string $key): array
    {
        return [
            'observed_on' => $date,
            'name' => $name,
            'scope' => 'event',
            'state' => null,
            'source_key' => $key,
        ];
    }
}
