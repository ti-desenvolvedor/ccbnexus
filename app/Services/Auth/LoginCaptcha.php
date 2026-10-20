<?php

namespace App\Services\Auth;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Str;

class LoginCaptcha
{
    public function issue(): void
    {
        session([
            'login.captcha' => encrypt($this->code()),
            'login.captcha_stamp' => Str::random(12),
        ]);
    }

    public function matches(string $input): bool
    {
        $code = $this->currentCode();

        if ($code === null) {
            return false;
        }

        return hash_equals($this->normalize($code), $this->normalize($input));
    }

    public function svg(): string
    {
        $code = $this->currentCode();

        if ($code === null) {
            $this->issue();
            $code = $this->currentCode() ?? '';
        }

        return $this->render($code);
    }

    public function stamp(): string
    {
        return (string) session('login.captcha_stamp', '');
    }

    private function currentCode(): ?string
    {
        $stored = session('login.captcha');

        if (! is_string($stored) || $stored === '') {
            return null;
        }

        try {
            $code = decrypt($stored);
        } catch (DecryptException) {
            return null;
        }

        return is_string($code) && $code !== '' ? $code : null;
    }

    private function code(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $length = strlen($alphabet);
        $code = '';

        for ($i = 0; $i < 5; $i++) {
            $code .= $alphabet[random_int(0, $length - 1)];
        }

        return $code;
    }

    private function normalize(string $value): string
    {
        return strtoupper((string) preg_replace('/\s+/', '', $value));
    }

    private function render(string $code): string
    {
        $letters = '';
        $x = 16;

        foreach (str_split($code) as $char) {
            $y = random_int(30, 40);
            $rotate = random_int(-16, 16);
            $safe = htmlspecialchars($char, ENT_QUOTES | ENT_XML1, 'UTF-8');
            $letters .= '<text x="'.$x.'" y="'.$y.'" transform="rotate('.$rotate.' '.$x.' '.$y.')" font-family="DejaVu Sans, sans-serif" font-size="26" font-weight="700" fill="#0f172a">'.$safe.'</text>';
            $x += 30;
        }

        $noise = '';

        for ($i = 0; $i < 6; $i++) {
            $noise .= '<line x1="'.random_int(0, 180).'" y1="'.random_int(0, 56).'" x2="'.random_int(0, 180).'" y2="'.random_int(0, 56).'" stroke="#94a3b8" stroke-width="1"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<svg xmlns="http://www.w3.org/2000/svg" width="180" height="56" viewBox="0 0 180 56">'
            .'<rect width="180" height="56" rx="8" fill="#f8fafc"/>'
            .$noise
            .$letters
            .'</svg>';
    }
}
