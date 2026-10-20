<?php

namespace App\Services\Agenda;

use DateTimeImmutable;

/**
 * Feriados civis nacionais e a data magna de cada UF.
 * Pontos facultativos (carnaval, Corpus Christi, vésperas) ficam de fora.
 */
class BrazilianHolidayCatalog
{
    /**
     * Datas fixas da data magna estadual (mês, dia, nome).
     * ES é móvel. DF e MG coincidem com Tiradentes; GO e MT não têm data magna civil própria nesta lista.
     *
     * @var array<string, array{0: int, 1: int, 2: string}>
     */
    private const STATE_FIXED = [
        'AC' => [6, 15, 'Elevação do Acre'],
        'AL' => [9, 16, 'Emancipação de Alagoas'],
        'AM' => [9, 5, 'Elevação do Amazonas'],
        'AP' => [9, 13, 'Data magna do Amapá'],
        'BA' => [7, 2, 'Independência da Bahia'],
        'CE' => [3, 25, 'Data magna do Ceará'],
        'MA' => [7, 28, 'Data magna do Maranhão'],
        'MS' => [10, 11, 'Criação de Mato Grosso do Sul'],
        'PA' => [8, 15, 'Adesão do Pará'],
        'PB' => [8, 5, 'Data magna da Paraíba'],
        'PE' => [3, 6, 'Revolução Pernambucana'],
        'PI' => [10, 19, 'Dia do Piauí'],
        'PR' => [12, 19, 'Emancipação do Paraná'],
        'RJ' => [4, 23, 'São Jorge'],
        'RN' => [10, 3, 'Mártires de Cunhaú e Uruaçu'],
        'RO' => [1, 4, 'Criação de Rondônia'],
        'RR' => [10, 5, 'Criação de Roraima'],
        'RS' => [9, 20, 'Revolução Farroupilha'],
        'SC' => [8, 11, 'Data magna de Santa Catarina'],
        'SE' => [7, 8, 'Independência de Sergipe'],
        'SP' => [7, 9, 'Revolução Constitucionalista'],
        'TO' => [10, 5, 'Criação do Tocantins'],
    ];

    /**
     * @return list<array{observed_on: string, name: string, scope: string, state: ?string, source_key: string}>
     */
    public function forYear(int $year, ?string $state = null): array
    {
        $rows = [
            $this->national($year, 1, 1, 'Confraternização Universal'),
            $this->national($year, 0, 0, 'Paixão de Cristo', $this->goodFriday($year)),
            $this->national($year, 4, 21, 'Tiradentes'),
            $this->national($year, 5, 1, 'Dia do Trabalho'),
            $this->national($year, 9, 7, 'Independência do Brasil'),
            $this->national($year, 10, 12, 'Nossa Senhora Aparecida'),
            $this->national($year, 11, 2, 'Finados'),
            $this->national($year, 11, 15, 'Proclamação da República'),
        ];

        if ($year >= 2024) {
            $rows[] = $this->national($year, 11, 20, 'Consciência Negra');
        }

        $rows[] = $this->national($year, 12, 25, 'Natal');

        $uf = $state !== null ? strtoupper($state) : '';
        if (isset(self::STATE_FIXED[$uf])) {
            [$month, $day, $name] = self::STATE_FIXED[$uf];
            $rows[] = $this->state($year, $uf, sprintf('%04d-%02d-%02d', $year, $month, $day), $name);
        }
        if ($uf === 'ES') {
            $rows[] = $this->state($year, 'ES', $this->penha($year), 'Nossa Senhora da Penha');
        }

        usort($rows, fn (array $a, array $b): int => $a['observed_on'] <=> $b['observed_on']);

        return $rows;
    }

    /**
     * Data magna de cada UF no ano. Uma linha por estado, chave estável.
     *
     * @return list<array{observed_on: string, name: string, scope: string, state: string, source_key: string}>
     */
    public function stateHolidays(int $year): array
    {
        $rows = [];
        foreach (array_keys(self::STATE_FIXED) as $uf) {
            foreach ($this->forYear($year, $uf) as $row) {
                if ($row['scope'] === 'state') {
                    $rows[] = $row;
                }
            }
        }

        foreach ($this->forYear($year, 'ES') as $row) {
            if ($row['scope'] === 'state') {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    public function easterSunday(int $year): string
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    public function goodFriday(int $year): string
    {
        return (new DateTimeImmutable($this->easterSunday($year)))->modify('-2 days')->format('Y-m-d');
    }

    /**
     * Segunda-feira oito dias depois da Páscoa (data observada no Espírito Santo).
     */
    public function penha(int $year): string
    {
        return (new DateTimeImmutable($this->easterSunday($year)))->modify('+8 days')->format('Y-m-d');
    }

    /**
     * @return array{observed_on: string, name: string, scope: string, state: null, source_key: string}
     */
    private function national(int $year, int $month, int $day, string $name, ?string $date = null): array
    {
        $observed = $date ?? sprintf('%04d-%02d-%02d', $year, $month, $day);

        return [
            'observed_on' => $observed,
            'name' => $name,
            'scope' => 'national',
            'state' => null,
            'source_key' => 'national:'.$observed,
        ];
    }

    /**
     * @return array{observed_on: string, name: string, scope: string, state: string, source_key: string}
     */
    private function state(int $year, string $uf, string $date, string $name): array
    {
        return [
            'observed_on' => $date,
            'name' => $name,
            'scope' => 'state',
            'state' => $uf,
            'source_key' => 'state:'.$uf.':'.$date,
        ];
    }
}
