<?php

use App\Livewire\Forms\LoginForm;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    public function mount(): void
    {
        $this->form->refreshCaptcha();
    }

    public function refreshCaptcha(): void
    {
        $this->form->refreshCaptcha();
    }

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: RouteServiceProvider::HOME, navigate: true);
    }
}; ?>

<div>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="login">
        <x-nexus.form-progress class="mb-4" />
        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="form.email" id="email" class="block mt-1 w-full" type="email" name="email" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input wire:model="form.password" id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />

            @if (Route::has('password.request'))
                <a
                    href="{{ route('password.request') }}"
                    wire:navigate
                    class="mt-1 inline-flex min-h-11 items-center text-sm font-semibold text-gray-800 underline decoration-gray-400 underline-offset-4 hover:text-gray-950 focus:outline-none focus-visible:rounded-md focus-visible:ring-2 focus-visible:ring-primary-500"
                >
                    Esqueci minha senha
                </a>
            @endif
        </div>

        @if ($form->captchaRequired())
            <div class="mt-4">
                <x-input-label for="captcha" value="Código da imagem" />
                <p class="mt-1 text-xs text-gray-500">Digite os caracteres da imagem. Depois de muitas tentativas o login fica bloqueado por 15 minutos.</p>
                <div class="mt-2 flex items-center gap-3">
                    <img
                        src="{{ route('login.captcha') }}?v={{ $form->captchaStamp() }}"
                        alt="Código de verificação"
                        width="180"
                        height="56"
                        class="rounded-md border border-gray-200 bg-white"
                    />
                    <button type="button" wire:click="refreshCaptcha" class="text-sm text-gray-600 underline">
                        Outro código
                    </button>
                </div>
                <x-text-input wire:model="form.captcha" id="captcha" class="block mt-2 w-full" type="text" name="captcha" required autocomplete="off" inputmode="text" />
                <x-input-error :messages="$errors->get('form.captcha')" class="mt-2" />
            </div>
        @endif

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember" class="inline-flex items-center">
                <input wire:model="form.remember" id="remember" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button wire:loading.attr="disabled" wire:target="login">
                <span wire:loading.remove wire:target="login">{{ __('Log in') }}</span>
                <span wire:loading wire:target="login">Entrando…</span>
            </x-primary-button>
        </div>
    </form>
</div>
