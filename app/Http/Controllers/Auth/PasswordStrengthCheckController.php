<?php

namespace App\Http\Controllers\Auth;

use App\Services\Auth\PwnedPasswordService;
use App\Support\PasswordStrength;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PasswordStrengthCheckController
{
    public function __invoke(Request $request, PwnedPasswordService $pwned): JsonResponse
    {
        $password = (string) $request->validate([
            'password' => ['required', 'string', 'max:200'],
        ])['password'];

        $level = PasswordStrength::level($password);
        $leaked = $level === PasswordStrength::WEAK ? false : $pwned->leaked($password);

        return response()->json([
            'level' => $level,
            'leaked' => $leaked,
            'label' => $leaked === true ? 'Vazada' : PasswordStrength::label($level),
        ]);
    }
}
