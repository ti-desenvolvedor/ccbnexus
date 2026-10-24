<?php

use App\Http\Controllers\Auth\LoginCaptchaController;
use App\Http\Controllers\Auth\PasswordStrengthCheckController;
use App\Http\Controllers\Auth\StopImpersonationController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware('guest')->group(function () {
    if (config('nexus.allow_public_registration')) {
        Volt::route('register', 'pages.auth.register')
            ->name('register');
    }

    Volt::route('login', 'pages.auth.login')
        ->middleware('throttle:10,1')
        ->name('login');

    Route::get('login/captcha', LoginCaptchaController::class)
        ->middleware('throttle:30,1')
        ->name('login.captcha');

    Volt::route('forgot-password', 'pages.auth.forgot-password')
        ->name('password.request');
});

Volt::route('reset-password/{token}', 'pages.auth.reset-password')
    ->name('password.reset');

Route::post('password-check', PasswordStrengthCheckController::class)
    ->middleware('throttle:20,1')
    ->name('password.check');

Route::middleware('auth')->group(function () {
    Route::post('logout', Logout::class)->name('logout');

    Route::post('impersonation/stop', StopImpersonationController::class)
        ->name('impersonation.stop');

    Volt::route('verify-email', 'pages.auth.verify-email')
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Volt::route('confirm-password', 'pages.auth.confirm-password')
        ->name('password.confirm');
});
