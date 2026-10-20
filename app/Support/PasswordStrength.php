<?php

namespace App\Support;

use Illuminate\Support\Facades\Hash;

class PasswordStrength
{
    public const STRONG = 'strong';

    public const WEAK = 'weak';

    public const UNKNOWN = 'unknown';

    public static function level(string $plain): string
    {
        $min = max(8, (int) config('nexus.auth.password_min', 8));
        $longEnough = mb_strlen($plain) >= $min;
        $hasLetter = preg_match('/\p{L}/u', $plain) === 1;
        $hasNumber = preg_match('/\p{N}/u', $plain) === 1;
        $hasSymbol = preg_match('/[^\p{L}\p{N}]/u', $plain) === 1;

        return $longEnough && $hasLetter && $hasNumber && $hasSymbol
            ? self::STRONG
            : self::WEAK;
    }

    /**
     * @return array{password: string, password_strength: string}
     */
    public static function stored(string $plain): array
    {
        return [
            'password' => Hash::make($plain),
            'password_strength' => self::level($plain),
        ];
    }

    public static function label(?string $level): string
    {
        return match ($level) {
            self::STRONG => 'Adequada',
            self::WEAK => 'Fraca',
            default => 'Não verificada',
        };
    }

    public static function tone(?string $level): string
    {
        return match ($level) {
            self::STRONG => 'success',
            self::WEAK => 'danger',
            default => 'warning',
        };
    }

    public static function needsChange(?string $level): bool
    {
        return $level !== self::STRONG;
    }
}
