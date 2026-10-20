<?php

namespace App\Services\Agenda;

use App\Models\Location;
use App\Models\Regional;

/**
 * Cidade e UF usadas para escolher o feriado estadual da regional.
 */
class RegionalHolidayPlace
{
    /** @var array<string, string> */
    private const UF_NAMES = [
        'AC' => 'Acre',
        'AL' => 'Alagoas',
        'AM' => 'Amazonas',
        'AP' => 'Amapá',
        'BA' => 'Bahia',
        'CE' => 'Ceará',
        'DF' => 'Distrito Federal',
        'ES' => 'Espírito Santo',
        'GO' => 'Goiás',
        'MA' => 'Maranhão',
        'MG' => 'Minas Gerais',
        'MS' => 'Mato Grosso do Sul',
        'MT' => 'Mato Grosso',
        'PA' => 'Pará',
        'PB' => 'Paraíba',
        'PE' => 'Pernambuco',
        'PI' => 'Piauí',
        'PR' => 'Paraná',
        'RJ' => 'Rio de Janeiro',
        'RN' => 'Rio Grande do Norte',
        'RO' => 'Rondônia',
        'RR' => 'Roraima',
        'RS' => 'Rio Grande do Sul',
        'SC' => 'Santa Catarina',
        'SE' => 'Sergipe',
        'SP' => 'São Paulo',
        'TO' => 'Tocantins',
    ];

    /**
     * @return array{city: ?string, state: ?string}
     */
    public function resolve(Regional $regional): array
    {
        $regional->loadMissing('location');
        $own = $this->fromLocation($regional->location);
        if ($own['state'] !== null || $own['city'] !== null) {
            return $own;
        }

        $locations = Location::query()
            ->where('regional_id', $regional->id)
            ->orderBy('id')
            ->get(['city', 'state']);

        $named = $locations->first(function (Location $location) use ($regional): bool {
            $city = $this->clean($location->city);

            return $city !== null && str_contains($this->fold($regional->name), $this->fold($city));
        });

        return $this->fromLocation($named ?? $locations->first());
    }

    public function stateLabel(?string $state): string
    {
        $uf = $state !== null ? strtoupper($state) : '';

        return self::UF_NAMES[$uf] ?? $uf;
    }

    /**
     * @return array{city: ?string, state: ?string}
     */
    private function fromLocation(?Location $location): array
    {
        if ($location === null) {
            return ['city' => null, 'state' => null];
        }

        return [
            'city' => $this->clean($location->city),
            'state' => $this->normalizeState($location->state),
        ];
    }

    private function normalizeState(?string $state): ?string
    {
        $value = $this->clean($state);
        if ($value === null) {
            return null;
        }

        $upper = $this->fold($value);
        if (isset(self::UF_NAMES[$upper])) {
            return $upper;
        }

        $byName = array_flip(array_map(fn (string $name): string => $this->fold($name), self::UF_NAMES));

        return $byName[$upper] ?? null;
    }

    private function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function fold(string $value): string
    {
        $value = mb_strtoupper(trim($value), 'UTF-8');

        return strtr($value, [
            'Á' => 'A', 'À' => 'A', 'Ã' => 'A', 'Â' => 'A',
            'É' => 'E', 'Ê' => 'E',
            'Í' => 'I',
            'Ó' => 'O', 'Õ' => 'O', 'Ô' => 'O',
            'Ú' => 'U',
            'Ç' => 'C',
        ]);
    }
}
