<?php

use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $email = '';

    /**
     * A resposta é sempre a mesma. Não informa se o e-mail está cadastrado.
     */
    public function sendPasswordResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        $ipKey = 'password-reset-ip|'.request()->ip();

        if (! RateLimiter::tooManyAttempts($ipKey, 5)) {
            RateLimiter::hit($ipKey, 60);

            Password::sendResetLink([
                'email' => $this->email,
            ]);
        }

        $this->reset('email');

        session()->flash('status', __('auth.password_reset_sent'));
    }
}; ?>

<div>
    <h1 class="text-lg font-semibold text-gray-900">Recuperar senha</h1>
    <p class="mb-4 mt-1 text-sm text-gray-700">
        Informe o e-mail da conta. Se ele estiver cadastrado, enviamos um link para escolher uma senha nova. A tela não diz se o e-mail existe, e a senha atual não aparece na mensagem.
    </p>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="sendPasswordResetLink">
        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" name="email" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4 flex flex-col-reverse items-stretch gap-2 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ route('login') }}" wire:navigate class="inline-flex min-h-11 items-center text-sm font-semibold text-gray-800 underline decoration-gray-400 underline-offset-4 focus:outline-none focus-visible:rounded-md focus-visible:ring-2 focus-visible:ring-primary-500">
                Voltar ao login
            </a>
            <x-primary-button wire:loading.attr="disabled" wire:target="sendPasswordResetLink">
                <span wire:loading.remove wire:target="sendPasswordResetLink">Enviar link de recuperação</span>
                <span wire:loading wire:target="sendPasswordResetLink">Enviando…</span>
            </x-primary-button>
        </div>
    </form>
</div>
