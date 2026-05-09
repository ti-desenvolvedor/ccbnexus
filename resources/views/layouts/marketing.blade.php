<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-palette="blue">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100" x-data x-init="$store.nexus.boot()">
        <header class="sticky top-0 z-30 border-b backdrop-blur" style="background-color: rgb(var(--topbar-bg) / 0.80); border-color: rgb(var(--topbar-border) / 0.80); color: rgb(var(--topbar-text) / 1);">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-3 px-4 sm:px-6">
                <a href="{{ route('home') }}" class="flex min-w-0 items-center rounded-lg outline-none ring-offset-2 ring-offset-transparent transition hover:opacity-90 focus-visible:ring-2 focus-visible:ring-primary-500">
                    <x-brand-logo />
                </a>

                <nav class="hidden items-center gap-6 text-sm font-semibold sm:flex">
                    <a href="{{ route('home') }}" class="hover:text-primary-600">Início</a>
                    <a href="{{ route('status') }}" class="hover:text-primary-600">Status</a>
                </nav>

                <div class="flex items-center gap-2">
                    <div class="relative hidden sm:block" x-data="{ open: false }">
                        <button
                            type="button"
                            class="flex items-center gap-2 rounded-xl border px-2.5 py-1.5 text-xs font-semibold text-[rgb(var(--topbar-text)_/_1)] hover:bg-[rgb(var(--topbar-muted)_/_0.12)]"
                            style="border-color: rgb(var(--topbar-border) / 0.90); background-color: rgb(var(--topbar-bg) / 0.55);"
                            @click="open = !open"
                            :aria-expanded="open"
                            aria-haspopup="true"
                            title="Tema e cor primária"
                        >
                            <svg class="h-4 w-4 shrink-0 opacity-90" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                <path stroke-width="2" stroke-linecap="round" d="M12 3a6 6 0 100 12 6 6 0 000-12z" />
                                <path stroke-width="2" stroke-linecap="round" d="M12 3v2M12 19v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M3 12h2M19 12h2" />
                            </svg>
                            <span class="max-w-[7rem] truncate sm:max-w-none">Aparência</span>
                            <svg class="h-3.5 w-3.5 shrink-0 opacity-70 transition" :class="{ 'rotate-180': open }" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                <path stroke-width="2" stroke-linecap="round" d="M6 9l6 6 6-6" />
                            </svg>
                        </button>
                        <div
                            x-cloak
                            x-show="open"
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="-translate-y-1 opacity-0"
                            x-transition:enter-end="translate-y-0 opacity-100"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="translate-y-0 opacity-100"
                            x-transition:leave-end="-translate-y-1 opacity-0"
                            @click.outside="open = false"
                            @keydown.escape.window="open = false"
                            class="absolute right-0 z-[60] mt-2 w-[17.5rem] origin-top-right overflow-hidden rounded-xl border shadow-xl ring-1 ring-black/5 dark:ring-white/10"
                            style="border-color: rgb(var(--topbar-border) / 0.92); background-color: rgb(var(--topbar-bg) / 1); color: rgb(var(--topbar-text) / 1);"
                            role="menu"
                        >
                            <div class="max-h-[min(24rem,calc(100dvh-6rem))] overflow-y-auto overscroll-contain p-2">
                                <div class="px-1 pb-1.5 text-[10px] font-semibold uppercase tracking-wide" style="color: rgb(var(--topbar-muted) / 1);">Tema</div>
                                <div class="flex rounded-lg border p-0.5" style="border-color: rgb(var(--topbar-border) / 0.85); background-color: rgb(var(--topbar-bg) / 0.5);">
                                    <button type="button" role="menuitem" class="flex-1 rounded-md px-2 py-2 text-center text-xs font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 focus-visible:ring-offset-[rgb(var(--topbar-bg)_/_1)] dark:focus-visible:ring-offset-slate-900" :class="$store.nexus.theme === 'light' ? 'bg-primary-600 text-white shadow-sm' : 'text-[rgb(var(--topbar-text)_/_1)] hover:bg-[rgb(var(--topbar-muted)_/_0.14)]'" @click="$store.nexus.setTheme('light'); open = false">Claro</button>
                                    <button type="button" role="menuitem" class="flex-1 rounded-md px-2 py-2 text-center text-xs font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 focus-visible:ring-offset-[rgb(var(--topbar-bg)_/_1)] dark:focus-visible:ring-offset-slate-900" :class="$store.nexus.theme === 'dark' ? 'bg-primary-600 text-white shadow-sm' : 'text-[rgb(var(--topbar-text)_/_1)] hover:bg-[rgb(var(--topbar-muted)_/_0.14)]'" @click="$store.nexus.setTheme('dark'); open = false">Escuro</button>
                                </div>
                                <div class="my-2 border-t" style="border-color: rgb(var(--topbar-border) / 0.65);"></div>
                                <div class="px-1 pb-1.5 text-[10px] font-semibold uppercase tracking-wide" style="color: rgb(var(--topbar-muted) / 1);">Cor primária</div>
                                <div class="grid grid-cols-2 gap-1">
                                    <button type="button" role="menuitem" class="rounded-lg px-2 py-2 text-center text-xs font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 focus-visible:ring-offset-[rgb(var(--topbar-bg)_/_1)] dark:focus-visible:ring-offset-slate-900" :class="$store.nexus.palette === 'blue' ? 'bg-primary-600 text-white shadow-sm' : 'text-[rgb(var(--topbar-text)_/_1)] hover:bg-[rgb(var(--topbar-muted)_/_0.14)]'" @click="$store.nexus.setPalette('blue'); open = false">Azul</button>
                                    <button type="button" role="menuitem" class="rounded-lg px-2 py-2 text-center text-xs font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 focus-visible:ring-offset-[rgb(var(--topbar-bg)_/_1)] dark:focus-visible:ring-offset-slate-900" :class="$store.nexus.palette === 'navy' ? 'bg-primary-600 text-white shadow-sm' : 'text-[rgb(var(--topbar-text)_/_1)] hover:bg-[rgb(var(--topbar-muted)_/_0.14)]'" @click="$store.nexus.setPalette('navy'); open = false">Azul marinho</button>
                                    <button type="button" role="menuitem" class="rounded-lg px-2 py-2 text-center text-xs font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 focus-visible:ring-offset-[rgb(var(--topbar-bg)_/_1)] dark:focus-visible:ring-offset-slate-900" :class="$store.nexus.palette === 'green' ? 'bg-primary-600 text-white shadow-sm' : 'text-[rgb(var(--topbar-text)_/_1)] hover:bg-[rgb(var(--topbar-muted)_/_0.14)]'" @click="$store.nexus.setPalette('green'); open = false">Verde</button>
                                    <button type="button" role="menuitem" class="rounded-lg px-2 py-2 text-center text-xs font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 focus-visible:ring-offset-[rgb(var(--topbar-bg)_/_1)] dark:focus-visible:ring-offset-slate-900" :class="$store.nexus.palette === 'green_dark' ? 'bg-primary-600 text-white shadow-sm' : 'text-[rgb(var(--topbar-text)_/_1)] hover:bg-[rgb(var(--topbar-muted)_/_0.14)]'" @click="$store.nexus.setPalette('green_dark'); open = false">Verde escuro</button>
                                    <button type="button" role="menuitem" class="rounded-lg px-2 py-2 text-center text-xs font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 focus-visible:ring-offset-[rgb(var(--topbar-bg)_/_1)] dark:focus-visible:ring-offset-slate-900" :class="$store.nexus.palette === 'red' ? 'bg-primary-600 text-white shadow-sm' : 'text-[rgb(var(--topbar-text)_/_1)] hover:bg-[rgb(var(--topbar-muted)_/_0.14)]'" @click="$store.nexus.setPalette('red'); open = false">Vermelho</button>
                                    <button type="button" role="menuitem" class="rounded-lg px-2 py-2 text-center text-xs font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 focus-visible:ring-offset-[rgb(var(--topbar-bg)_/_1)] dark:focus-visible:ring-offset-slate-900" :class="$store.nexus.palette === 'red_dark' ? 'bg-primary-600 text-white shadow-sm' : 'text-[rgb(var(--topbar-text)_/_1)] hover:bg-[rgb(var(--topbar-muted)_/_0.14)]'" @click="$store.nexus.setPalette('red_dark'); open = false">Vermelho escuro</button>
                                    <button type="button" role="menuitem" class="rounded-lg px-2 py-2 text-center text-xs font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 focus-visible:ring-offset-[rgb(var(--topbar-bg)_/_1)] dark:focus-visible:ring-offset-slate-900" :class="$store.nexus.palette === 'orange' ? 'bg-primary-600 text-white shadow-sm' : 'text-[rgb(var(--topbar-text)_/_1)] hover:bg-[rgb(var(--topbar-muted)_/_0.14)]'" @click="$store.nexus.setPalette('orange'); open = false">Laranja</button>
                                    <button type="button" role="menuitem" class="rounded-lg px-2 py-2 text-center text-xs font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 focus-visible:ring-offset-[rgb(var(--topbar-bg)_/_1)] dark:focus-visible:ring-offset-slate-900" :class="$store.nexus.palette === 'brown' ? 'bg-primary-600 text-white shadow-sm' : 'text-[rgb(var(--topbar-text)_/_1)] hover:bg-[rgb(var(--topbar-muted)_/_0.14)]'" @click="$store.nexus.setPalette('brown'); open = false">Marrom</button>
                                </div>
                            </div>
                        </div>
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

        <style>[x-cloak] { display: none !important; }</style>
    </body>
</html>

