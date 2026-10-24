<?php

namespace App\Support;

use Illuminate\Support\Facades\Hash;

class PasswordStrength
{
    public const STRONG = 'strong';

    public const MEDIUM = 'medium';

    public const WEAK = 'weak';

    public const UNKNOWN = 'unknown';

    public const STRONG_LENGTH = 12;

    public static function minLength(): int
    {
        return max(8, (int) config('nexus.auth.password_min', 8));
    }

    public static function level(string $plain): string
    {
        $length = mb_strlen($plain);
        $hasLetter = preg_match('/\p{L}/u', $plain) === 1;
        $hasNumber = preg_match('/\p{N}/u', $plain) === 1;
        $hasSymbol = preg_match('/[\p{Z}\p{S}\p{P}]/u', $plain) === 1;

        $hasMixed = preg_match('/\p{Ll}/u', $plain) === 1 && preg_match('/\p{Lu}/u', $plain) === 1;

        if ($plain === '' || $length < self::minLength() || ! $hasLetter || ! $hasNumber || ! $hasSymbol || ! $hasMixed) {
            return self::WEAK;
        }

        if ($length >= self::STRONG_LENGTH) {
            return self::STRONG;
        }

        return self::MEDIUM;
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
            self::STRONG => 'Forte',
            self::MEDIUM => 'Média',
            self::WEAK => 'Fraca',
            default => 'Não verificada',
        };
    }

    public static function tone(?string $level): string
    {
        return match ($level) {
            self::STRONG => 'success',
            self::MEDIUM => 'warning',
            self::WEAK => 'danger',
            default => 'warning',
        };
    }

    public static function needsChange(?string $level): bool
    {
        return $level === null || $level === self::WEAK || $level === self::UNKNOWN;
    }
}
