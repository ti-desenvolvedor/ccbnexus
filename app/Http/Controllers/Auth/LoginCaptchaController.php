<?php

namespace App\Http\Controllers\Auth;

use App\Services\Auth\LoginCaptcha;
use Illuminate\Http\Response;

class LoginCaptchaController
{
    public function __invoke(LoginCaptcha $captcha): Response
    {
        return response($captcha->svg(), 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
