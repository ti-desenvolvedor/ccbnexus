<?php

namespace App\Services;

use App\Support\BrazilianStates;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Lista municípios por UF (API IBGE, cacheada).
 */
class IbgeMunicipioService
{
    private const CACHE_TTL_SECONDS = 604800;

    /**
     * @return list<string> nomes oficiais dos municípios, ordenados
     */
    public function municipalityNamesForUf(string $uf): array
    {
        $uf = strtoupper(substr(trim($uf), 0, 2));
        if (! BrazilianStates::isValidUf($uf)) {
            return [];
        }

        $stateId = $this->resolveStateId($uf);
        if ($stateId === null) {
            return [];
        }

        $cacheKey = 'ibge.municipios.'.$uf;

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($stateId) {
            $response = Http::timeout(20)
                ->acceptJson()
                ->get('https://servicodados.ibge.gov.br/api/v1/localidades/estados/'.$stateId.'/municipios');

            if (! $response->successful()) {
                return [];
            }

            $list = $response->json();
            if (! is_array($list)) {
                return [];
            }

            $names = [];
            foreach ($list as $row) {
                if (is_array($row) && isset($row['nome'])) {
                    $names[] = (string) $row['nome'];
                }
            }
            sort($names, SORT_LOCALE_STRING);

            return array_values(array_unique($names));
        });
    }

    private function resolveStateId(string $uf): ?int
    {
        $map = Cache::remember('ibge.estado_sigla_para_id', self::CACHE_TTL_SECONDS, function () {
            $response = Http::timeout(15)
                ->acceptJson()
                ->get('https://servicodados.ibge.gov.br/api/v1/localidades/estados');

            if (! $response->successful()) {
                return [];
            }

            $list = $response->json();
            if (! is_array($list)) {
                return [];
            }

            $out = [];
            foreach ($list as $row) {
                if (is_array($row) && array_key_exists('sigla', $row) && array_key_exists('id', $row)) {
                    $sigla = strtoupper((string) $row['sigla']);
                    $out[$sigla] = (int) $row['id'];
                }
            }

            return $out;
        });

        return $map[$uf] ?? null;
    }
}
