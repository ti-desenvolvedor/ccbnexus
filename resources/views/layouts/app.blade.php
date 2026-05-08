<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-primary="blue" data-sidebar-skin="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('styles')
    </head>
    <body
        class="font-sans antialiased bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100"
        x-data
        x-init="$store.nexus.boot(@js([
            'org' => request()->routeIs(['organization.*', 'access.requests.*', 'users.*']),
            'ops' => request()->routeIs('infrastructure.*'),
            'agenda' => request()->routeIs(['agenda.*', 'reports.*']),
        ]))"
    >
        <div class="min-h-screen">
            <!-- Mobile sidebar overlay -->
            <div
                x-cloak
                x-show="$store.nexus.sidebarOpen"
                x-transition.opacity
                class="fixed inset-0 z-40 bg-slate-950/60 lg:hidden"
                @click="$store.nexus.closeSidebar()"
            ></div>

            <!-- Sidebar -->
            <aside
                class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-slate-200/80 bg-white/90 backdrop-blur dark:border-slate-800/80 dark:bg-slate-950/70 lg:translate-x-0"
                :class="{
                    '-translate-x-full': !$store.nexus.sidebarOpen,
                    'translate-x-0': $store.nexus.sidebarOpen,
                    'lg:w-20': $store.nexus.sidebarCollapsed,
                    'lg:w-72': !$store.nexus.sidebarCollapsed,
                }"
                style="background-color: rgb(var(--sidebar-bg) / 0.92); border-color: rgb(var(--sidebar-border) / 1); color: rgb(var(--sidebar-text) / 1);"
            >
                <div class="flex h-16 items-center gap-2 px-4">
                    <div class="grid h-9 w-9 place-items-center rounded-xl bg-primary-600 text-sm font-bold text-white">
                        NX
                    </div>
                    <div class="min-w-0" x-show="!$store.nexus.sidebarCollapsed" x-transition>
                        <div class="truncate text-sm font-semibold">{{ config('app.name', 'CCB Nexus') }}</div>
                        <div class="truncate text-xs" style="color: rgb(var(--sidebar-muted) / 1);">Painel</div>
                    </div>
                    <button
                        type="button"
                        class="ml-auto hidden rounded-lg p-2 text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-900 lg:inline-flex"
                        @click="$store.nexus.toggleCollapse()"
                        title="Recolher menu"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-width="2" stroke-linecap="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>

                <nav class="flex-1 space-y-6 overflow-y-auto px-3 pb-6 pt-2">
                    <div>
                        <div class="px-2 pb-2 text-[11px] font-semibold uppercase tracking-wide" style="color: rgb(var(--sidebar-muted) / 1);" x-show="!$store.nexus.sidebarCollapsed">Menu</div>
                        <div class="space-y-1">
                            <a
                                href="{{ route('dashboard') }}"
                                @click="$store.nexus.ensureSidebarExpandedForNavigation()"
                                title="Dashboard"
                                @class([
                                    'flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium',
                                    'bg-primary-600/20 text-white ring-1 ring-primary-500/30' => request()->routeIs('dashboard'),
                                    'text-[rgb(var(--sidebar-text)/1)]' => ! request()->routeIs('dashboard'),
                                ])
                                :class="!@js(request()->routeIs('dashboard')) && ($store.nexus.sidebarSkin === 'dark' ? 'hover:bg-white/10' : 'hover:bg-slate-100')"
                            >
                                <span
                                    @class([
                                        'grid h-9 w-9 place-items-center rounded-lg',
                                        'bg-white/15 text-white' => request()->routeIs('dashboard'),
                                        'text-[rgb(var(--sidebar-text)/1)]' => ! request()->routeIs('dashboard'),
                                    ])
                                    :class="!@js(request()->routeIs('dashboard')) && ($store.nexus.sidebarSkin === 'dark' ? 'bg-white/10' : 'bg-slate-100')"
                                >
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-width="2" stroke-linecap="round" d="M4 13h4v7H4zM10 3h4v17h-4zM16 8h4v12h-4z" />
                                    </svg>
                                </span>
                                <span class="truncate" x-show="!$store.nexus.sidebarCollapsed">Dashboard</span>
                            </a>

                            <div class="pt-2">
                                <button
                                    type="button"
                                    class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left text-sm font-semibold"
                                    :class="$store.nexus.sidebarSkin === 'dark' ? 'hover:bg-white/10' : 'hover:bg-slate-100'"
                                    style="color: rgb(var(--sidebar-text) / 1);"
                                    title="Organização"
                                    @click="$store.nexus.toggleMenu('org')"
                                >
                                    <span class="grid h-9 w-9 place-items-center rounded-lg" :class="$store.nexus.sidebarSkin === 'dark' ? 'bg-white/10' : 'bg-slate-100'">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-width="2" stroke-linecap="round" d="M4 7h16M4 12h16M4 17h10" />
                                        </svg>
                                    </span>
                                    <span class="min-w-0 flex-1 truncate" x-show="!$store.nexus.sidebarCollapsed">Organização</span>
                                    <svg x-show="!$store.nexus.sidebarCollapsed" class="h-4 w-4 text-[rgb(var(--sidebar-muted)/1)] transition" :class="{ 'rotate-90': $store.nexus.menu.org }" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-width="2" stroke-linecap="round" d="M9 6l6 6-6 6" />
                                    </svg>
                                </button>

                                <div x-show="!$store.nexus.sidebarCollapsed && $store.nexus.menu.org" x-transition class="mt-1 space-y-1 pl-2">
                                    @auth
                                        @can('viewAny', \App\Models\Regional::class)
                                            <a href="{{ route('organization.regionals.index') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="block rounded-lg px-3 py-2 text-sm" :class="$store.nexus.sidebarSkin === 'dark' ? 'hover:bg-white/10' : 'hover:bg-slate-100'" style="color: rgb(var(--sidebar-muted) / 1);">Regionais</a>
                                        @endcan
                                        @can('viewAny', \App\Models\Administration::class)
                                            <a href="{{ route('organization.administrations.index') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="block rounded-lg px-3 py-2 text-sm" :class="$store.nexus.sidebarSkin === 'dark' ? 'hover:bg-white/10' : 'hover:bg-slate-100'" style="color: rgb(var(--sidebar-muted) / 1);">Administrações</a>
                                        @endcan
                                        @can('viewAny', \App\Models\PrayerHouse::class)
                                            <a href="{{ route('organization.prayer-houses.index') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="block rounded-lg px-3 py-2 text-sm" :class="$store.nexus.sidebarSkin === 'dark' ? 'hover:bg-white/10' : 'hover:bg-slate-100'" style="color: rgb(var(--sidebar-muted) / 1);">Casas de oração</a>
                                        @endcan
                                        @can('viewAny', \App\Models\AccessRequest::class)
                                            <a href="{{ route('access.requests.index') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="block rounded-lg px-3 py-2 text-sm" :class="$store.nexus.sidebarSkin === 'dark' ? 'hover:bg-white/10' : 'hover:bg-slate-100'" style="color: rgb(var(--sidebar-muted) / 1);">Pedidos de acesso</a>
                                        @endcan
                                        @can('viewAny', \App\Models\User::class)
                                            <a href="{{ route('users.index') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="block rounded-lg px-3 py-2 text-sm" :class="$store.nexus.sidebarSkin === 'dark' ? 'hover:bg-white/10' : 'hover:bg-slate-100'" style="color: rgb(var(--sidebar-muted) / 1);">Utilizadores</a>
                                        @endcan
                                    @endauth
                                </div>
                            </div>

                            <div class="pt-1">
                                <button
                                    type="button"
                                    class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left text-sm font-semibold"
                                    :class="$store.nexus.sidebarSkin === 'dark' ? 'hover:bg-white/10' : 'hover:bg-slate-100'"
                                    style="color: rgb(var(--sidebar-text) / 1);"
                                    title="Operação"
                                    @click="$store.nexus.toggleMenu('ops')"
                                >
                                    <span class="grid h-9 w-9 place-items-center rounded-lg" :class="$store.nexus.sidebarSkin === 'dark' ? 'bg-white/10' : 'bg-slate-100'">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-width="2" stroke-linecap="round" d="M8 7V3M16 7V3M5 11h14M7 7h10a2 2 0 012 2v9a2 2 0 01-2 2H7a2 2 0 01-2-2V9a2 2 0 012-2z" />
                                        </svg>
                                    </span>
                                    <span class="min-w-0 flex-1 truncate" x-show="!$store.nexus.sidebarCollapsed">Operação</span>
                                    <svg x-show="!$store.nexus.sidebarCollapsed" class="h-4 w-4 text-[rgb(var(--sidebar-muted)/1)] transition" :class="{ 'rotate-90': $store.nexus.menu.ops }" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-width="2" stroke-linecap="round" d="M9 6l6 6-6 6" />
                                    </svg>
                                </button>

                                <div x-show="!$store.nexus.sidebarCollapsed && $store.nexus.menu.ops" x-transition class="mt-1 space-y-1 pl-2">
                                    @auth
                                        @can('viewAny', \App\Models\Location::class)
                                            <a href="{{ route('infrastructure.locations.index') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="block rounded-lg px-3 py-2 text-sm" :class="$store.nexus.sidebarSkin === 'dark' ? 'hover:bg-white/10' : 'hover:bg-slate-100'" style="color: rgb(var(--sidebar-muted) / 1);">Locais</a>
                                        @endcan
                                        @can('viewAny', \App\Models\MeetingRoom::class)
                                            <a href="{{ route('infrastructure.meeting-rooms.index') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="block rounded-lg px-3 py-2 text-sm" :class="$store.nexus.sidebarSkin === 'dark' ? 'hover:bg-white/10' : 'hover:bg-slate-100'" style="color: rgb(var(--sidebar-muted) / 1);">Salas</a>
                                        @endcan
                                        @can('viewAny', \App\Models\RoomReservation::class)
                                            <a href="{{ route('infrastructure.room-reservations.index') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="block rounded-lg px-3 py-2 text-sm" :class="$store.nexus.sidebarSkin === 'dark' ? 'hover:bg-white/10' : 'hover:bg-slate-100'" style="color: rgb(var(--sidebar-muted) / 1);">Reservas</a>
                                        @endcan
                                        @can('viewAny', \App\Models\Parking::class)
                                            <a href="{{ route('infrastructure.parkings.index') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="block rounded-lg px-3 py-2 text-sm" :class="$store.nexus.sidebarSkin === 'dark' ? 'hover:bg-white/10' : 'hover:bg-slate-100'" style="color: rgb(var(--sidebar-muted) / 1);">Estacionamentos</a>
                                        @endcan
                                    @endauth
                                </div>
                            </div>

                            <div class="pt-1">
                                <button
                                    type="button"
                                    class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left text-sm font-semibold"
                                    :class="$store.nexus.sidebarSkin === 'dark' ? 'hover:bg-white/10' : 'hover:bg-slate-100'"
                                    style="color: rgb(var(--sidebar-text) / 1);"
                                    title="Agenda"
                                    @click="$store.nexus.toggleMenu('agenda')"
                                >
                                    <span class="grid h-9 w-9 place-items-center rounded-lg" :class="$store.nexus.sidebarSkin === 'dark' ? 'bg-white/10' : 'bg-slate-100'">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-width="2" stroke-linecap="round" d="M8 7V3m8 4V3M5 11h14M5 21h14V11H5v10z" />
                                        </svg>
                                    </span>
                                    <span class="min-w-0 flex-1 truncate" x-show="!$store.nexus.sidebarCollapsed">Agenda</span>
                                    <svg x-show="!$store.nexus.sidebarCollapsed" class="h-4 w-4 text-[rgb(var(--sidebar-muted)/1)] transition" :class="{ 'rotate-90': $store.nexus.menu.agenda }" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-width="2" stroke-linecap="round" d="M9 6l6 6-6 6" />
                                    </svg>
                                </button>

                                <div x-show="!$store.nexus.sidebarCollapsed && $store.nexus.menu.agenda" x-transition class="mt-1 space-y-1 pl-2">
                                    @auth
                                        @can('viewAny', \App\Models\Event::class)
                                            <a href="{{ route('agenda.events.index') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="block rounded-lg px-3 py-2 text-sm" :class="$store.nexus.sidebarSkin === 'dark' ? 'hover:bg-white/10' : 'hover:bg-slate-100'" style="color: rgb(var(--sidebar-muted) / 1);">Eventos</a>
                                        @endcan
                                        @can('viewAny', \App\Models\PublicGroup::class)
                                            <a href="{{ route('agenda.public-catalog.index') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="block rounded-lg px-3 py-2 text-sm" :class="$store.nexus.sidebarSkin === 'dark' ? 'hover:bg-white/10' : 'hover:bg-slate-100'" style="color: rgb(var(--sidebar-muted) / 1);">{{ __('Catálogo público') }}</a>
                                        @endcan
                                        @can('viewAny', \App\Models\Audience::class)
                                            <a href="{{ route('agenda.audiences.index') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="block rounded-lg px-3 py-2 text-sm" :class="$store.nexus.sidebarSkin === 'dark' ? 'hover:bg-white/10' : 'hover:bg-slate-100'" style="color: rgb(var(--sidebar-muted) / 1);">{{ __('Audiências (legado)') }}</a>
                                        @endcan
                                        @can('viewAny', \App\Models\Approval::class)
                                            <a href="{{ route('agenda.approvals.index') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="block rounded-lg px-3 py-2 text-sm" :class="$store.nexus.sidebarSkin === 'dark' ? 'hover:bg-white/10' : 'hover:bg-slate-100'" style="color: rgb(var(--sidebar-muted) / 1);">Aprovações</a>
                                        @endcan
                                        @can('visualizar_relatorios')
                                            <a href="{{ route('reports.events.csv') }}" @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="block rounded-lg px-3 py-2 text-sm" :class="$store.nexus.sidebarSkin === 'dark' ? 'hover:bg-white/10' : 'hover:bg-slate-100'" style="color: rgb(var(--sidebar-muted) / 1);">Exportar eventos (CSV)</a>
                                        @endcan
                                        @can('gerenciar_avisos')
                                            <a href="{{ route('agenda.whatsapp.index') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="block rounded-lg px-3 py-2 text-sm" :class="$store.nexus.sidebarSkin === 'dark' ? 'hover:bg-white/10' : 'hover:bg-slate-100'" style="color: rgb(var(--sidebar-muted) / 1);">Notificações WhatsApp</a>
                                        @endcan
                                        <span class="block rounded-lg px-3 py-2 text-xs text-slate-500">Avisos: comando agendado <code class="text-[10px]">nexus:event-reminders</code></span>
                                    @endauth
                                </div>
                            </div>
                        </div>
                    </div>
                </nav>

                <div class="border-t border-slate-200/80 p-3 dark:border-slate-800/80">
                    <div class="rounded-xl bg-slate-50 p-3 text-xs text-slate-600 dark:bg-slate-900 dark:text-slate-300" x-show="!$store.nexus.sidebarCollapsed">
                        <div class="font-semibold text-slate-800 dark:text-slate-100">Dica</div>
                        <div class="mt-1 leading-relaxed">
                            Este menu já nasce “SaaS”: colapsável, tema e cor primária persistidos no navegador.
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Main -->
            <div
                class="min-h-screen transition-[padding] duration-200 lg:pl-72"
                :class="{ 'lg:pl-20': $store.nexus.sidebarCollapsed }"
            >
                <!-- Topbar -->
                <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/80 backdrop-blur dark:border-slate-800/80 dark:bg-slate-950/60">
                    <div class="mx-auto flex h-16 w-full max-w-full items-center gap-3 px-4 sm:px-6 lg:px-8">
                        <button
                            type="button"
                            class="inline-flex rounded-lg p-2 text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-900 lg:hidden"
                            @click="$store.nexus.toggleSidebar()"
                            aria-label="Abrir menu"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-width="2" stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>

                        <div class="hidden min-w-0 flex-1 md:block">
                            <label class="relative block">
                                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-width="2" stroke-linecap="round" d="M21 21l-4.3-4.3M11 18a7 7 0 100-14 7 7 0 000 14z" />
                                    </svg>
                                </span>
                                <input
                                    class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100"
                                    placeholder="Buscar…"
                                    type="search"
                                />
                            </label>
                        </div>

                        <div class="ml-auto flex items-center gap-2">
                            <div class="hidden items-center gap-1 rounded-xl border border-slate-200 bg-white p-1 dark:border-slate-800 dark:bg-slate-950 sm:flex">
                                <button type="button" class="rounded-lg px-2 py-1 text-xs font-semibold hover:bg-slate-100 dark:hover:bg-slate-900" @click="$store.nexus.setPrimary('blue')">Azul</button>
                                <button type="button" class="rounded-lg px-2 py-1 text-xs font-semibold hover:bg-slate-100 dark:hover:bg-slate-900" @click="$store.nexus.setPrimary('green')">Verde</button>
                                <button type="button" class="rounded-lg px-2 py-1 text-xs font-semibold hover:bg-slate-100 dark:hover:bg-slate-900" @click="$store.nexus.setPrimary('purple')">Roxo</button>
                            </div>

                            <div class="hidden items-center gap-1 rounded-xl border border-slate-200 bg-white p-1 dark:border-slate-800 dark:bg-slate-950 lg:flex">
                                <button type="button" class="rounded-lg px-2 py-1 text-xs font-semibold hover:bg-slate-100 dark:hover:bg-slate-900" @click="$store.nexus.setSidebarSkin('dark')" title="Sidebar escura">Barra escura</button>
                                <button type="button" class="rounded-lg px-2 py-1 text-xs font-semibold hover:bg-slate-100 dark:hover:bg-slate-900" @click="$store.nexus.setSidebarSkin('light')" title="Sidebar clara">Barra clara</button>
                            </div>

                            <button
                                type="button"
                                class="rounded-lg p-2 text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-900"
                                @click="$store.nexus.toggleTheme()"
                                title="Alternar tema"
                            >
                                <svg x-show="$store.nexus.theme !== 'dark'" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-width="2" stroke-linecap="round" d="M12 3v2M12 19v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M3 12h2M19 12h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4" />
                                    <path stroke-width="2" stroke-linecap="round" d="M12 8a4 4 0 100 8 4 4 0 000-8z" />
                                </svg>
                                <svg x-show="$store.nexus.theme === 'dark'" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-width="2" stroke-linecap="round" d="M21 14.3A8.5 8.5 0 0110.2 3.7 6.7 6.7 0 0012 21a8.5 8.5 0 009-6.7z" />
                                </svg>
                            </button>

                            <div class="relative" x-data="{ open:false }">
                                <button type="button" class="relative rounded-lg p-2 text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-900" @click="open=!open">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-width="2" stroke-linecap="round" d="M15 17h5l-1.4-1.4M5 7a7 7 0 0114 0v1a5 5 0 01-1 3.3V17H6v-5.7A5 5 0 015 8V7z" />
                                    </svg>
                                    <span class="absolute right-1 top-1 inline-flex h-2 w-2 rounded-full bg-rose-500"></span>
                                </button>
                                <div x-cloak x-show="open" @click.outside="open=false" class="absolute right-0 mt-2 w-72 rounded-xl border border-slate-200 bg-white p-3 text-sm shadow-lg dark:border-slate-800 dark:bg-slate-950">
                                    <div class="font-semibold">Notificações</div>
                                    <div class="mt-2 text-slate-600 dark:text-slate-300">Sem itens por enquanto (próximo passo: módulo de Avisos).</div>
                                </div>
                            </div>

                            <div class="relative" x-data="{ open:false }">
                                <button type="button" class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-2 py-1.5 text-sm hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-950 dark:hover:bg-slate-900" @click="open=!open">
                                    <span class="grid h-8 w-8 place-items-center rounded-full bg-primary-600 text-xs font-bold text-white">
                                        {{ strtoupper(mb_substr(auth()->user()->name ?? 'U', 0, 1)) }}
                                    </span>
                                    <span class="hidden max-w-[10rem] truncate sm:block">{{ auth()->user()->name }}</span>
                                    <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-width="2" stroke-linecap="round" d="M6 9l6 6 6-6" />
                                    </svg>
                                </button>
                                <div x-cloak x-show="open" @click.outside="open=false" class="absolute right-0 mt-2 w-56 rounded-xl border border-slate-200 bg-white py-1 text-sm shadow-lg dark:border-slate-800 dark:bg-slate-950">
                                    <a href="{{ route('profile') }}" class="block px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-900">Perfil</a>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="block w-full px-3 py-2 text-left hover:bg-slate-50 dark:hover:bg-slate-900">Sair</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    @isset($header)
                        <div class="border-t border-slate-200/80 bg-white/60 px-4 py-4 dark:border-slate-800/80 dark:bg-slate-950/40 sm:px-6 lg:px-8">
                            <div class="mx-auto w-full max-w-full">
                                {{ $header }}
                            </div>
                        </div>
                    @endisset
                </header>

                <main class="mx-auto w-full max-w-full px-4 py-8 sm:px-6 lg:px-8">
                    @if (session('status'))
                        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-100" role="status">
                            {{ session('status') }}
                        </div>
                    @endif
                    {{ $slot }}
                </main>

                <footer class="mx-auto w-full max-w-full px-4 pb-10 pt-2 text-center text-xs text-slate-500 dark:text-slate-400 sm:px-6 lg:px-8">
                    CCB Nexus — layout base (Tailwind + Livewire + Alpine)
                </footer>
            </div>
        </div>

        <style>
            [x-cloak] { display: none !important; }
        </style>
        @stack('scripts')
    </body>
</html>
