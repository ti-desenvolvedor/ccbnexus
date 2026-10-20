<?php

namespace App\Services\Agenda;

use Illuminate\Support\Facades\Http;

/**
 * Nacionais do ano. A BrasilAPI recalcula os móveis; a lista local cobre a falha da rede.
 */
class NationalHolidayFeed
{
    public function __construct(
        protected BrazilianHolidayCatalog $catalog,
    ) {}

    private string $lastSource = 'catalog';

    public function lastSource(): string
    {
        return $this->lastSource;
    }

    /**
     * @return list<array{observed_on: string, name: string, scope: string, state: null, source_key: string}>
     */
    public function forYear(int $year): array
    {
        if (config('nexus.holiday_sync.driver') === 'brasilapi') {
            $remote = $this->fromBrasilApi($year);
            if ($remote !== null) {
                $this->lastSource = 'brasilapi';

                return $remote;
            }
        }

        $this->lastSource = 'catalog';

        return array_values(array_filter(
            $this->catalog->forYear($year),
            fn (array $row): bool => $row['scope'] === 'national',
        ));
    }

    /**
     * @return list<array{observed_on: string, name: string, scope: string, state: null, source_key: string}>|null
     */
    private function fromBrasilApi(int $year): ?array
    {
        try {
            $response = Http::timeout(8)
                ->acceptJson()
                ->get('https://brasilapi.com.br/api/feriados/v1/'.$year);
        } catch (\Throwable) {
            return null;
        }

        if (! $response->ok() || ! is_array($response->json())) {
            return null;
        }

        $rows = [];
        foreach ($response->json() as $item) {
            if (! is_array($item)) {
                continue;
            }
            $date = (string) ($item['date'] ?? '');
            $name = trim((string) ($item['name'] ?? ''));
            $type = (string) ($item['type'] ?? 'national');
            if ($type !== 'national' || $name === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                continue;
            }
            if ($this->isOptional($name)) {
                continue;
            }
            $rows[] = [
                'observed_on' => $date,
                'name' => $name,
                'scope' => 'national',
                'state' => null,
                'source_key' => 'national:'.$date,
            ];
        }

        return $rows === [] ? null : $rows;
    }

    private function isOptional(string $name): bool
    {
        $folded = strtr(mb_strtolower($name, 'UTF-8'), [
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a',
            'é' => 'e', 'ê' => 'e',
            'í' => 'i',
            'ó' => 'o', 'õ' => 'o', 'ô' => 'o',
            'ú' => 'u',
            'ç' => 'c',
        ]);

        foreach (['carnaval', 'cinzas', 'corpus christi', 'vespera'] as $needle) {
            if (str_contains($folded, $needle)) {
                return true;
            }
        }

        return false;
    }
}
