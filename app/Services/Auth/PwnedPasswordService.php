<?php

namespace App\Services\Auth;

use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Support\Facades\Http;
use Throwable;

class PwnedPasswordService implements UncompromisedVerifier
{
    /**
     * True when the password appears in a known breach.
     * Null when the breach service cannot be reached. The caller decides whether to fail open.
     */
    public function leaked(string $password): ?bool
    {
        if ($password === '') {
            return null;
        }

        $hash = strtoupper(sha1($password));
        $prefix = substr($hash, 0, 5);
        $suffix = substr($hash, 5);

        try {
            $response = Http::withHeaders([
                'Add-Padding' => 'true',
            ])->timeout(4)->get('https://api.pwnedpasswords.com/range/'.$prefix);
        } catch (Throwable $e) {
            report($e);

            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        foreach (preg_split("/\r\n|\n|\r/", $response->body()) ?: [] as $line) {
            if (! str_contains($line, ':')) {
                continue;
            }

            [$found, $count] = explode(':', trim($line), 2);
            if (hash_equals($suffix, strtoupper($found)) && (int) $count > 0) {
                return true;
            }
        }

        return false;
    }

    public function verify($data): bool
    {
        $leaked = $this->leaked((string) ($data['value'] ?? ''));

        return $leaked === null ? true : ! $leaked;
    }
}
