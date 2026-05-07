<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Consulta CEP nos Correios via API pública ViaCEP (sem chave).
 */
class CepLookupService
{
    /**
     * @return array{
     *     line1?: string,
     *     complement?: string,
     *     district?: string,
     *     city?: string,
     *     state?: string,
     *     postal_code?: string
     * }|null null se CEP inválido ou não encontrado
     */
    public function lookup(string $cep): ?array
    {
        $digits = preg_replace('/\D/', '', $cep) ?? '';
        if (strlen($digits) !== 8) {
            return null;
        }

        $response = Http::timeout(8)
            ->acceptJson()
            ->get('https://viacep.com.br/ws/'.$digits.'/json/');

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();
        if (! is_array($data) || ($data['erro'] ?? false) === true) {
            return null;
        }

        $line1 = trim((string) ($data['logradouro'] ?? ''));
        $district = trim((string) ($data['bairro'] ?? ''));
        $city = trim((string) ($data['localidade'] ?? ''));
        $uf = strtoupper(trim((string) ($data['uf'] ?? '')));
        if (strlen($uf) > 2) {
            $uf = substr($uf, 0, 2);
        }

        $complement = trim((string) ($data['complemento'] ?? ''));

        return [
            'line1' => $line1 !== '' ? $line1 : null,
            'complement' => $complement !== '' ? $complement : null,
            'district' => $district !== '' ? $district : null,
            'city' => $city !== '' ? $city : null,
            'state' => $uf !== '' ? $uf : null,
            'postal_code' => $this->formatCep($digits),
        ];
    }

    public function formatCep(string $digits): string
    {
        $digits = Str::substr(preg_replace('/\D/', '', $digits) ?? '', 0, 8);

        return strlen($digits) === 8
            ? Str::substr($digits, 0, 5).'-'.Str::substr($digits, 5, 3)
            : $digits;
    }
}
