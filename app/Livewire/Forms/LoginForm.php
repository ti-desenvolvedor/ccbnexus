<?php

namespace App\Livewire\Forms;

use App\Services\Auth\LoginCaptcha;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Form;

class LoginForm extends Form
{
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    #[Validate('boolean')]
    public bool $remember = false;

    public string $captcha = '';

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();
        $this->ensureCaptchaMatches();

        if (! Auth::attempt($this->only(['email', 'password']), $this->remember)) {
            $this->registerFailure();

            throw ValidationException::withMessages([
                'form.email' => trans('auth.failed'),
            ]);
        }

        $this->clearChallenges();
    }

    public function captchaRequired(): bool
    {
        return Cache::get($this->captchaFlagKey()) === true
            || RateLimiter::attempts($this->throttleKey()) >= $this->captchaAfter();
    }

    public function captchaStamp(): string
    {
        return app(LoginCaptcha::class)->stamp();
    }

    public function refreshCaptcha(): void
    {
        if ($this->captchaRequired()) {
            app(LoginCaptcha::class)->issue();
        }
    }

    protected function ensureCaptchaMatches(): void
    {
        if (! $this->captchaRequired()) {
            return;
        }

        $captcha = app(LoginCaptcha::class);

        if ($captcha->matches($this->captcha)) {
            return;
        }

        $this->registerFailure();

        throw ValidationException::withMessages([
            'form.captcha' => trans('auth.captcha'),
        ]);
    }

    protected function registerFailure(): void
    {
        RateLimiter::hit($this->throttleKey(), $this->lockSeconds());

        if (RateLimiter::attempts($this->throttleKey()) >= $this->captchaAfter()) {
            Cache::put($this->captchaFlagKey(), true, now()->addHours(12));
        }

        if ($this->captchaRequired()) {
            app(LoginCaptcha::class)->issue();
            $this->captcha = '';
        }

        if (RateLimiter::attempts($this->throttleKey()) >= $this->maxAttempts()) {
            $this->lock();
            RateLimiter::clear($this->throttleKey());
            $this->throwLockout();
        }
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        $until = Cache::get($this->lockKey());

        if (! is_numeric($until) || (int) $until <= now()->timestamp) {
            return;
        }

        event(new Lockout(request()));

        $seconds = (int) $until - now()->timestamp;

        throw ValidationException::withMessages([
            'form.email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => (int) ceil($seconds / 60),
            ]),
        ]);
    }

    protected function lock(): void
    {
        Cache::put($this->lockKey(), now()->addSeconds($this->lockSeconds())->timestamp, $this->lockSeconds());
    }

    protected function throwLockout(): void
    {
        event(new Lockout(request()));

        $seconds = $this->lockSeconds();

        throw ValidationException::withMessages([
            'form.email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => (int) ceil($seconds / 60),
            ]),
        ]);
    }

    protected function clearChallenges(): void
    {
        RateLimiter::clear($this->throttleKey());
        Cache::forget($this->captchaFlagKey());
        Cache::forget($this->lockKey());
        session()->forget(['login.captcha', 'login.captcha_stamp']);
    }

    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }

    protected function captchaFlagKey(): string
    {
        return 'login-captcha|'.$this->throttleKey();
    }

    protected function lockKey(): string
    {
        return 'login-lock|'.$this->throttleKey();
    }

    protected function maxAttempts(): int
    {
        return max(1, (int) config('nexus.auth.max_attempts', 5));
    }

    protected function captchaAfter(): int
    {
        return max(1, (int) config('nexus.auth.captcha_after', 2));
    }

    protected function lockSeconds(): int
    {
        return max(60, (int) config('nexus.auth.lock_seconds', 900));
    }
}
