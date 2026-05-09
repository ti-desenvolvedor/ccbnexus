<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-palette="blue">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'CCB Nexus') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100" x-data x-init="$store.nexus.boot()">
        <header class="sticky top-0 z-30 border-b backdrop-blur" style="background-color: rgb(var(--topbar-bg) / 0.80); border-color: rgb(var(--topbar-border) / 0.80); color: rgb(var(--topbar-text) / 1);">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-3 px-4 sm:px-6">
                <a href="{{ route('home') }}" class="flex items-center gap-2 font-semibold">
                    <span class="grid h-9 w-9 place-items-center rounded-xl bg-primary-600 text-sm font-bold text-white">NX</span>
                    <span class="hidden sm:block">{{ config('app.name', 'CCB Nexus') }}</span>
                </a>

                <nav class="hidden items-center gap-6 text-sm font-semibold sm:flex">
                    <a href="{{ route('home') }}" class="hover:text-primary-600">Início</a>
                    <a href="{{ route('status') }}" class="hover:text-primary-600">Status</a>
                </nav>

                <div class="flex items-center gap-2">
                    <div class="hidden items-center gap-1 rounded-xl border p-1 sm:flex" style="border-color: rgb(var(--topbar-border) / 0.90); background-color: rgb(var(--topbar-bg) / 0.55);">
                        <button type="button" class="rounded-lg px-2 py-1 text-xs font-semibold hover:bg-[rgb(var(--topbar-muted)/0.14)]" :class="$store.nexus.palette === 'green' ? 'bg-primary-600 text-white' : 'text-[rgb(var(--topbar-text)/1)]'" @click="$store.nexus.setPalette('green')">Verde</button>
                        <button type="button" class="rounded-lg px-2 py-1 text-xs font-semibold hover:bg-[rgb(var(--topbar-muted)/0.14)]" :class="$store.nexus.palette === 'green_dark' ? 'bg-primary-600 text-white' : 'text-[rgb(var(--topbar-text)/1)]'" @click="$store.nexus.setPalette('green_dark')">Verde escuro</button>
                        <button type="button" class="rounded-lg px-2 py-1 text-xs font-semibold hover:bg-[rgb(var(--topbar-muted)/0.14)]" :class="$store.nexus.palette === 'red' ? 'bg-primary-600 text-white' : 'text-[rgb(var(--topbar-text)/1)]'" @click="$store.nexus.setPalette('red')">Vermelho</button>
                        <button type="button" class="rounded-lg px-2 py-1 text-xs font-semibold hover:bg-[rgb(var(--topbar-muted)/0.14)]" :class="$store.nexus.palette === 'red_dark' ? 'bg-primary-600 text-white' : 'text-[rgb(var(--topbar-text)/1)]'" @click="$store.nexus.setPalette('red_dark')">Vermelho escuro</button>
                        <button type="button" class="rounded-lg px-2 py-1 text-xs font-semibold hover:bg-[rgb(var(--topbar-muted)/0.14)]" :class="$store.nexus.palette === 'blue' ? 'bg-primary-600 text-white' : 'text-[rgb(var(--topbar-text)/1)]'" @click="$store.nexus.setPalette('blue')">Azul</button>
                        <button type="button" class="rounded-lg px-2 py-1 text-xs font-semibold hover:bg-[rgb(var(--topbar-muted)/0.14)]" :class="$store.nexus.palette === 'navy' ? 'bg-primary-600 text-white' : 'text-[rgb(var(--topbar-text)/1)]'" @click="$store.nexus.setPalette('navy')">Azul marinho</button>
                        <button type="button" class="rounded-lg px-2 py-1 text-xs font-semibold hover:bg-[rgb(var(--topbar-muted)/0.14)]" :class="$store.nexus.palette === 'orange' ? 'bg-primary-600 text-white' : 'text-[rgb(var(--topbar-text)/1)]'" @click="$store.nexus.setPalette('orange')">Laranja</button>
                        <button type="button" class="rounded-lg px-2 py-1 text-xs font-semibold hover:bg-[rgb(var(--topbar-muted)/0.14)]" :class="$store.nexus.palette === 'brown' ? 'bg-primary-600 text-white' : 'text-[rgb(var(--topbar-text)/1)]'" @click="$store.nexus.setPalette('brown')">Marrom</button>
                    </div>
                    <button type="button" class="rounded-lg p-2 text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-900" @click="$store.nexus.toggleTheme()" title="Alternar tema">
                        <svg x-show="$store.nexus.theme !== 'dark'" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-width="2" stroke-linecap="round" d="M12 3v2M12 19v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M3 12h2M19 12h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4" />
                            <path stroke-width="2" stroke-linecap="round" d="M12 8a4 4 0 100 8 4 4 0 000-8z" />
                        </svg>
                        <svg x-show="$store.nexus.theme === 'dark'" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-width="2" stroke-linecap="round" d="M21 14.3A8.5 8.5 0 0110.2 3.7 6.7 6.7 0 0012 21a8.5 8.5 0 009-6.7z" />
                        </svg>
                    </button>

                    @if (Route::has('login'))
                        <a href="{{ route('login') }}" class="hidden rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 sm:inline-flex">Entrar</a>
                    @endif
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-6xl px-4 py-10 sm:px-6">
            @yield('content')
        </main>
    </body>
</html>

