@props([
    'input',
])

@once
    <style>
        @keyframes nexus-password-spin { to { transform: rotate(360deg); } }
        @keyframes nexus-password-scan {
            0% { transform: translateX(-120%); }
            100% { transform: translateX(320%); }
        }
    </style>
@endonce

<div
    class="mt-2"
    x-data="nexusPasswordMeter(@js($input), {{ \App\Support\PasswordStrength::minLength() }}, {{ \App\Support\PasswordStrength::STRONG_LENGTH }})"
    data-password-check-url="{{ route('password.check') }}"
    x-cloak
>
    <div class="h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800" x-show="level !== ''" x-cloak>
        <div class="h-full rounded-full" x-show="!checking" x-bind:style="barStyle"></div>
        <div class="h-full rounded-full" x-show="checking" x-cloak style="width:35%;background:#64748b;animation:nexus-password-scan 1s ease-in-out infinite"></div>
    </div>
    <p class="mt-1 flex items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-200" role="status" aria-live="polite" x-show="checking" x-cloak>
        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="animation: nexus-password-spin .7s linear infinite">
            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" opacity=".25"></circle>
            <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
        </svg>
        Analisando senha…
    </p>
    <p class="mt-1 text-xs font-semibold" role="status" aria-live="polite" x-show="!checking && label !== ''" x-cloak x-bind:style="labelStyle" x-text="label"></p>
    <p class="mt-1 text-xs text-slate-600 dark:text-slate-300" x-show="!checking && note !== ''" x-cloak x-text="note"></p>
    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('auth.password_rules') }}</p>
</div>
