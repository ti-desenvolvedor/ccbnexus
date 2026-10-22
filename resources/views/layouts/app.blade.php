<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-palette="blue">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name') }}</title>

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
        x-init="$store.nexus.boot(@js(array_filter([
            'org' => request()->routeIs(['organization.*', 'access.requests.*', 'users.*']),
            'ops' => request()->routeIs('infrastructure.*'),
            'agenda' => request()->routeIs(['agenda.*', 'reports.*']),
            'secretaria' => request()->routeIs('secretaria.*'),
            'facilitator' => request()->routeIs(['access.requests.*', 'agenda.approvals.*', 'facilitator.*']),
            'theme' => auth()->user()?->resolvedUiTheme(),
            'palette' => auth()->user()?->resolvedUiPalette(),
            'sidebar' => auth()->user()?->resolvedUiSidebar(),
        ])))"
        x-on:sidebar-density-updated.window="if ($event.detail && $event.detail.density) { $store.nexus.applySidebarDensity($event.detail.density) }"
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
                class="nexus-sidebar fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-slate-200/80 bg-white/90 backdrop-blur dark:border-slate-800/80 dark:bg-slate-950/70 lg:translate-x-0"
                :class="{
                    '-translate-x-full': !$store.nexus.sidebarOpen,
                    'translate-x-0': $store.nexus.sidebarOpen,
                    'is-rail': $store.nexus.isSidebarRail(),
                    'is-peek': $store.nexus.sidebarDensity === 'icons' && $store.nexus.sidebarPeek,
                }"
                @mouseenter="$store.nexus.armSidebarPeek()"
                @mouseleave="$store.nexus.releaseSidebarPeek()"
                @focusin="$store.nexus.armSidebarPeek()"
                @focusout="if (!$el.contains($event.relatedTarget)) { $store.nexus.releaseSidebarPeek() }"
                style="background-color: rgb(var(--sidebar-bg) / 0.92); border-color: rgb(var(--sidebar-border) / 1); color: rgb(var(--sidebar-text) / 1);"
            >
                <div class="sidebar-brand flex h-16 items-center px-4">
                    <x-brand-logo class="min-w-0 flex-1">
                        <x-slot name="subtitle">
                            <span class="truncate text-xs font-medium text-[rgb(var(--sidebar-muted)_/_1)]">{{ __('Painel') }}</span>
                        </x-slot>
                    </x-brand-logo>
                </div>

                <nav class="flex-1 space-y-6 overflow-y-auto px-3 pb-6 pt-2">
                    <div>
                        <div class="sidebar-kicker px-2 pb-2 text-[11px] font-semibold uppercase tracking-wide" style="color: rgb(var(--sidebar-muted) / 1);">{{ __('Menu') }}</div>
                        <div class="space-y-1">
                            <a
                                href="{{ route('dashboard') }}"
                                @click="$store.nexus.ensureSidebarExpandedForNavigation()"
                                title="{{ __('Painel') }}"
                                class="sidebar-root flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium"
                                :class="$store.nexus.isActiveExact('/') || $store.nexus.isActivePrefix('/dashboard') ? 'bg-primary-600 text-white shadow-sm ring-1 ring-primary-500/40' : ($store.nexus.theme === 'dark' ? 'bg-slate-900/50 shadow-sm ring-1 ring-slate-800/70 text-[rgb(var(--sidebar-text)/1)] hover:bg-white/10' : 'bg-white shadow-sm ring-1 ring-slate-200/70 text-[rgb(var(--sidebar-text)/1)] hover:bg-slate-50')"
                                :aria-current="($store.nexus.isActiveExact('/') || $store.nexus.isActivePrefix('/dashboard')) ? 'page' : null"
                            >
                                <span
                                    class="grid h-9 w-9 place-items-center rounded-lg"
                                    :class="($store.nexus.isActiveExact('/') || $store.nexus.isActivePrefix('/dashboard')) ? 'bg-white/15 text-white' : ($store.nexus.theme === 'dark' ? 'bg-white/10 text-[rgb(var(--sidebar-text)/1)]' : 'bg-slate-50 text-[rgb(var(--sidebar-text)/1)] ring-1 ring-slate-200/60')"
                                >
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-width="2" stroke-linecap="round" d="M4 13h4v7H4zM10 3h4v17h-4zM16 8h4v12h-4z" />
                                    </svg>
                                </span>
                                <span class="sidebar-copy truncate">{{ __('Painel') }}</span>
                            </a>

                            @auth
                                @if (auth()->user()->can('atuar_como_facilitador') || auth()->user()->can('aprovar_acesso') || auth()->user()->can('aprovar_evento') || auth()->user()->can('aprovar_reserva_sala') || auth()->user()->isSuperAdmin())
                                    @php
                                        $pendingAccess = \App\Models\AccessRequest::query()->where('status', 'pending')->count();
                                        $pendingApprovals = \App\Models\Approval::query()->where('status', 'pending')->count();
                                        $pendingReservations = \App\Models\RoomReservation::query()->where('status', 'pending')->count();
                                        $facilitatorBadge = $pendingAccess + $pendingApprovals + $pendingReservations;
                                    @endphp
                                    <div class="pt-2">
                                        <button
                                            type="button"
                                            class="sidebar-root flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left text-sm font-semibold"
                                            :class="$store.nexus.isActivePrefix(['/access','/agenda/approvals','/facilitator']) ? 'bg-primary-600 text-white shadow-sm ring-1 ring-primary-500/40' : ($store.nexus.theme === 'dark' ? 'bg-slate-900/50 shadow-sm ring-1 ring-slate-800/70 text-[rgb(var(--sidebar-text)/1)] hover:bg-white/10' : 'bg-white shadow-sm ring-1 ring-slate-200/70 text-[rgb(var(--sidebar-text)/1)] hover:bg-slate-50')"
                                            aria-label="{{ __('Facilitador: pedidos, aprovações e reservas') }}"
                                            @click="$store.nexus.toggleMenu('facilitator')"
                                            :aria-expanded="$store.nexus.menu.facilitator"
                                        >
                                            <span class="grid h-9 w-9 place-items-center rounded-lg" :class="$store.nexus.isActivePrefix(['/access','/agenda/approvals','/facilitator']) ? 'bg-white/15 text-white' : ($store.nexus.theme === 'dark' ? 'bg-white/10' : 'bg-slate-50 ring-1 ring-slate-200/60')">
                                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="2" stroke-linecap="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                            </span>
                                            <span class="sidebar-copy min-w-0 flex-1 truncate">{{ __('Facilitador') }}</span>
                                            @if ($facilitatorBadge > 0)
                                                <span class="sidebar-badge rounded-full bg-rose-500 px-2 py-0.5 text-[10px] font-bold text-white">{{ $facilitatorBadge > 99 ? '99+' : $facilitatorBadge }}</span>
                                            @endif
                                            <svg class="sidebar-chevron h-4 w-4 transition" :class="{ 'rotate-90': $store.nexus.menu.facilitator }" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="2" stroke-linecap="round" d="M9 6l6 6-6 6" /></svg>
                                        </button>
                                        <div x-show="$store.nexus.menu.facilitator" x-transition class="sidebar-branch mt-1 space-y-1 pl-2">
                                            @can('viewAny', \App\Models\AccessRequest::class)
                                                <a href="{{ route('access.requests.index') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="flex min-h-11 items-center justify-between gap-2 rounded-lg px-3 py-2 text-sm hover:bg-slate-100 dark:hover:bg-white/10" :aria-current="$store.nexus.isActivePrefix('/access/requests') ? 'page' : null">
                                                    <span>{{ __('Pedidos de acesso') }}</span>
                                                    @if ($pendingAccess)<span class="text-[10px] font-bold text-rose-500">{{ $pendingAccess }}</span>@endif
                                                </a>
                                            @endcan
                                            @can('viewAny', \App\Models\Approval::class)
                                                <a href="{{ route('agenda.approvals.index') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="flex min-h-11 items-center justify-between gap-2 rounded-lg px-3 py-2 text-sm hover:bg-slate-100 dark:hover:bg-white/10" :aria-current="$store.nexus.isActivePrefix('/agenda/approvals') ? 'page' : null">
                                                    <span>{{ __('Aprovações') }}</span>
                                                    @if ($pendingApprovals)<span class="text-[10px] font-bold text-rose-500">{{ $pendingApprovals }}</span>@endif
                                                </a>
                                            @endcan
                                            @can('create', \App\Models\Event::class)
                                                <a href="{{ route('agenda.events.create') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-slate-100 dark:hover:bg-white/10">{{ __('Novo evento') }}</a>
                                            @endcan
                                            @can('create', \App\Models\RoomReservation::class)
                                                <a href="{{ route('infrastructure.room-reservations.create') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-slate-100 dark:hover:bg-white/10">{{ __('Nova reserva') }}</a>
                                                <a href="{{ route('infrastructure.room-reservations.calendar') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-slate-100 dark:hover:bg-white/10">{{ __('Calendário de salas') }}</a>
                                            @endcan
                                            @can('gerenciar_avisos')
                                                <a href="{{ route('agenda.whatsapp.index') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-slate-100 dark:hover:bg-white/10">{{ __('WhatsApp') }}</a>
                                            @endcan
                                        </div>
                                    </div>
                                @endif
                            @endauth

                            <div class="pt-2">
                                <button
                                    type="button"
                                    class="sidebar-root flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left text-sm font-semibold"
                                    :class="$store.nexus.isActivePrefix(['/organization','/users','/access']) ? 'bg-primary-600 text-white shadow-sm ring-1 ring-primary-500/40' : ($store.nexus.theme === 'dark' ? 'bg-slate-900/50 shadow-sm ring-1 ring-slate-800/70 text-[rgb(var(--sidebar-text)/1)] hover:bg-white/10' : 'bg-white shadow-sm ring-1 ring-slate-200/70 text-[rgb(var(--sidebar-text)/1)] hover:bg-slate-50')"
                                    title="{{ __('Organização') }}"
                                    @click="$store.nexus.toggleMenu('org')"
                                    :aria-expanded="$store.nexus.menu.org || $store.nexus.isActivePrefix(['/organization','/users','/access'])"
                                >
                                    <span class="grid h-9 w-9 place-items-center rounded-lg" :class="$store.nexus.isActivePrefix(['/organization','/users','/access']) ? 'bg-white/15 text-white' : ($store.nexus.theme === 'dark' ? 'bg-white/10' : 'bg-slate-50 ring-1 ring-slate-200/60')">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-width="2" stroke-linecap="round" d="M4 7h16M4 12h16M4 17h10" />
                                        </svg>
                                    </span>
                                    <span class="sidebar-copy min-w-0 flex-1 truncate">{{ __('Organização') }}</span>
                                    <svg class="sidebar-chevron h-4 w-4 transition" :class="{ 'rotate-90': $store.nexus.menu.org, 'text-white/90': $store.nexus.isActivePrefix(['/organization','/users','/access']), 'text-[rgb(var(--sidebar-muted)/1)]': !$store.nexus.isActivePrefix(['/organization','/users','/access']) }" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-width="2" stroke-linecap="round" d="M9 6l6 6-6 6" />
                                    </svg>
                                </button>

                                <div x-show="$store.nexus.menu.org || $store.nexus.isActivePrefix(['/organization','/users','/access'])" x-transition class="sidebar-branch mt-1 space-y-1 pl-2">
                                    @auth
                                        @can('viewAny', \App\Models\Regional::class)
                                            <a href="{{ route('organization.regionals.index') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm" :class="$store.nexus.isActivePrefix('/organization/regionals') ? 'bg-primary-600 text-white shadow-sm ring-1 ring-primary-500/40' : ($store.nexus.theme === 'dark' ? 'text-[rgb(var(--sidebar-muted)/1)] hover:bg-white/10' : 'text-[rgb(var(--sidebar-muted)/1)] hover:bg-slate-100')" :aria-current="$store.nexus.isActivePrefix('/organization/regionals') ? 'page' : null">
                                                <svg class="h-4 w-4 shrink-0 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="2" stroke-linecap="round" d="M12 2a7 7 0 00-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 00-7-7z"/><circle cx="12" cy="9" r="2.5" stroke-width="2"/></svg>
                                                <span>{{ __('Regionais') }}</span>
                                            </a>
                                        @endcan
                                        @can('viewAny', \App\Models\Administration::class)
                                            <a href="{{ route('organization.administrations.index') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm" :class="$store.nexus.isActivePrefix('/organization/administrations') ? 'bg-primary-600 text-white shadow-sm ring-1 ring-primary-500/40' : ($store.nexus.theme === 'dark' ? 'text-[rgb(var(--sidebar-muted)/1)] hover:bg-white/10' : 'text-[rgb(var(--sidebar-muted)/1)] hover:bg-slate-100')" :aria-current="$store.nexus.isActivePrefix('/organization/administrations') ? 'page' : null">
                                                <svg class="h-4 w-4 shrink-0 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="2" stroke-linecap="round" d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/></svg>
                                                <span>{{ __('Administrações') }}</span>
                                            </a>
                                        @endcan
                                        @can('viewAny', \App\Models\PrayerHouse::class)
                                            <a href="{{ route('organization.prayer-houses.index') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm" :class="$store.nexus.isActivePrefix('/organization/prayer-houses') ? 'bg-primary-600 text-white shadow-sm ring-1 ring-primary-500/40' : ($store.nexus.theme === 'dark' ? 'text-[rgb(var(--sidebar-muted)/1)] hover:bg-white/10' : 'text-[rgb(var(--sidebar-muted)/1)] hover:bg-slate-100')" :aria-current="$store.nexus.isActivePrefix('/organization/prayer-houses') ? 'page' : null">
                                                <svg class="h-4 w-4 shrink-0 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="2" stroke-linecap="round" d="M3 21h18M5 21V10l7-7 7 7v11M10 21v-4h4v4"/></svg>
                                                <span>{{ __('Casas de oração') }}</span>
                                            </a>
                                        @endcan
                                        @can('viewAny', \App\Models\AccessRequest::class)
                                            <a href="{{ route('access.requests.index') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm" :class="$store.nexus.isActivePrefix('/access/requests') ? 'bg-primary-600 text-white shadow-sm ring-1 ring-primary-500/40' : ($store.nexus.theme === 'dark' ? 'text-[rgb(var(--sidebar-muted)/1)] hover:bg-white/10' : 'text-[rgb(var(--sidebar-muted)/1)] hover:bg-slate-100')" :aria-current="$store.nexus.isActivePrefix('/access/requests') ? 'page' : null">
                                                <svg class="h-4 w-4 shrink-0 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="2" stroke-linecap="round" d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4M10 17l5-5-5-5M13 12H3"/></svg>
                                                <span>{{ __('Pedidos de acesso') }}</span>
                                            </a>
                                        @endcan
                                        @can('viewAny', \App\Models\User::class)
                                            <a href="{{ route('users.index') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm" :class="$store.nexus.isActivePrefix('/users') ? 'bg-primary-600 text-white shadow-sm ring-1 ring-primary-500/40' : ($store.nexus.theme === 'dark' ? 'text-[rgb(var(--sidebar-muted)/1)] hover:bg-white/10' : 'text-[rgb(var(--sidebar-muted)/1)] hover:bg-slate-100')" :aria-current="$store.nexus.isActivePrefix('/users') ? 'page' : null">
                                                <svg class="h-4 w-4 shrink-0 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="2" stroke-linecap="round" d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
                                                <span>{{ __('Usuários') }}</span>
                                            </a>
                                        @endcan
                                        @can('viewUserRoleDirectory')
                                            <a href="{{ route('users.roles') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm" :class="$store.nexus.isActiveExact('/users/roles') ? 'bg-primary-600 text-white shadow-sm ring-1 ring-primary-500/40' : ($store.nexus.theme === 'dark' ? 'text-[rgb(var(--sidebar-muted)/1)] hover:bg-white/10' : 'text-[rgb(var(--sidebar-muted)/1)] hover:bg-slate-100')">
                                                <span>{{ __('Papéis') }}</span>
                                            </a>
                                        @endcan
                                    @endauth
                                </div>
                            </div>

                            <div class="pt-1">
                                <button
                                    type="button"
                                    class="sidebar-root flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left text-sm font-semibold"
                                    :class="$store.nexus.isActivePrefix('/infrastructure') ? 'bg-primary-600 text-white shadow-sm ring-1 ring-primary-500/40' : ($store.nexus.theme === 'dark' ? 'bg-slate-900/50 shadow-sm ring-1 ring-slate-800/70 text-[rgb(var(--sidebar-text)/1)] hover:bg-white/10' : 'bg-white shadow-sm ring-1 ring-slate-200/70 text-[rgb(var(--sidebar-text)/1)] hover:bg-slate-50')"
                                    title="{{ __('Operação') }}"
                                    @click="$store.nexus.toggleMenu('ops')"
                                    :aria-expanded="$store.nexus.menu.ops || $store.nexus.isActivePrefix('/infrastructure')"
                                >
                                    <span class="grid h-9 w-9 place-items-center rounded-lg" :class="$store.nexus.isActivePrefix('/infrastructure') ? 'bg-white/15 text-white' : ($store.nexus.theme === 'dark' ? 'bg-white/10' : 'bg-slate-50 ring-1 ring-slate-200/60')">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-width="2" stroke-linecap="round" d="M8 7V3M16 7V3M5 11h14M7 7h10a2 2 0 012 2v9a2 2 0 01-2 2H7a2 2 0 01-2-2V9a2 2 0 012-2z" />
                                        </svg>
                                    </span>
                                    <span class="sidebar-copy min-w-0 flex-1 truncate">{{ __('Operação') }}</span>
                                    <svg class="sidebar-chevron h-4 w-4 transition" :class="{ 'rotate-90': $store.nexus.menu.ops, 'text-white/90': $store.nexus.isActivePrefix('/infrastructure'), 'text-[rgb(var(--sidebar-muted)/1)]': !$store.nexus.isActivePrefix('/infrastructure') }" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-width="2" stroke-linecap="round" d="M9 6l6 6-6 6" />
                                    </svg>
                                </button>

                                <div x-show="$store.nexus.menu.ops || $store.nexus.isActivePrefix('/infrastructure')" x-transition class="sidebar-branch mt-1 space-y-1 pl-2">
                                    @auth
                                        @can('viewAny', \App\Models\Location::class)
                                            <a href="{{ route('infrastructure.locations.index') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm" :class="$store.nexus.isActivePrefix('/infrastructure/locations') ? 'bg-primary-600 text-white shadow-sm ring-1 ring-primary-500/40' : ($store.nexus.theme === 'dark' ? 'text-[rgb(var(--sidebar-muted)/1)] hover:bg-white/10' : 'text-[rgb(var(--sidebar-muted)/1)] hover:bg-slate-100')" :aria-current="$store.nexus.isActivePrefix('/infrastructure/locations') ? 'page' : null">
                                                <svg class="h-4 w-4 shrink-0 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="2" stroke-linecap="round" d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 1118 0z"/><circle cx="12" cy="10" r="3" stroke-width="2"/></svg>
                                                <span>{{ __('Locais') }}</span>
                                            </a>
                                        @endcan
                                        @can('viewAny', \App\Models\MeetingRoom::class)
                                            <a href="{{ route('infrastructure.meeting-rooms.index') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm" :class="$store.nexus.isActivePrefix('/infrastructure/meeting-rooms') ? 'bg-primary-600 text-white shadow-sm ring-1 ring-primary-500/40' : ($store.nexus.theme === 'dark' ? 'text-[rgb(var(--sidebar-muted)/1)] hover:bg-white/10' : 'text-[rgb(var(--sidebar-muted)/1)] hover:bg-slate-100')" :aria-current="$store.nexus.isActivePrefix('/infrastructure/meeting-rooms') ? 'page' : null">
                                                <svg class="h-4 w-4 shrink-0 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="3" width="7" height="9" rx="1" stroke-width="2"/><rect x="14" y="3" width="7" height="9" rx="1" stroke-width="2"/><rect x="3" y="15" width="18" height="6" rx="1" stroke-width="2"/></svg>
                                                <span>{{ __('Salas') }}</span>
                                            </a>
                                        @endcan
                                        @can('viewAny', \App\Models\RoomReservation::class)
                                            <a href="{{ route('infrastructure.room-reservations.index') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm" :class="$store.nexus.isActivePrefix('/infrastructure/room-reservations') ? 'bg-primary-600 text-white shadow-sm ring-1 ring-primary-500/40' : ($store.nexus.theme === 'dark' ? 'text-[rgb(var(--sidebar-muted)/1)] hover:bg-white/10' : 'text-[rgb(var(--sidebar-muted)/1)] hover:bg-slate-100')" :aria-current="$store.nexus.isActivePrefix('/infrastructure/room-reservations') ? 'page' : null">
                                                <svg class="h-4 w-4 shrink-0 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="2" stroke-linecap="round" d="M8 7V3m8 4V3M3 11h18M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z"/><path stroke-width="2" stroke-linecap="round" d="M9 16l2 2 4-4"/></svg>
                                                <span>{{ __('Reservas') }}</span>
                                            </a>
                                        @endcan
                                        @can('viewAny', \App\Models\Parking::class)
                                            <a href="{{ route('infrastructure.parkings.index') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm" :class="$store.nexus.isActivePrefix('/infrastructure/parkings') ? 'bg-primary-600 text-white shadow-sm ring-1 ring-primary-500/40' : ($store.nexus.theme === 'dark' ? 'text-[rgb(var(--sidebar-muted)/1)] hover:bg-white/10' : 'text-[rgb(var(--sidebar-muted)/1)] hover:bg-slate-100')" :aria-current="$store.nexus.isActivePrefix('/infrastructure/parkings') ? 'page' : null">
                                                <svg class="h-4 w-4 shrink-0 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="3" width="18" height="18" rx="3" stroke-width="2"/><path stroke-width="2" stroke-linecap="round" d="M9 17V7h4a3 3 0 010 6H9"/></svg>
                                                <span>{{ __('Estacionamentos') }}</span>
                                            </a>
                                        @endcan
                                    @endauth
                                </div>
                            </div>

                            <div class="pt-1">
                                <button
                                    type="button"
                                    class="sidebar-root flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left text-sm font-semibold"
                                    :class="$store.nexus.isActivePrefix(['/agenda','/reports']) ? 'bg-primary-600 text-white shadow-sm ring-1 ring-primary-500/40' : ($store.nexus.theme === 'dark' ? 'bg-slate-900/50 shadow-sm ring-1 ring-slate-800/70 text-[rgb(var(--sidebar-text)/1)] hover:bg-white/10' : 'bg-white shadow-sm ring-1 ring-slate-200/70 text-[rgb(var(--sidebar-text)/1)] hover:bg-slate-50')"
                                    title="{{ __('Agenda') }}"
                                    @click="$store.nexus.toggleMenu('agenda')"
                                    :aria-expanded="$store.nexus.menu.agenda || $store.nexus.isActivePrefix(['/agenda','/reports'])"
                                >
                                    <span class="grid h-9 w-9 place-items-center rounded-lg" :class="$store.nexus.isActivePrefix(['/agenda','/reports']) ? 'bg-white/15 text-white' : ($store.nexus.theme === 'dark' ? 'bg-white/10' : 'bg-slate-50 ring-1 ring-slate-200/60')">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-width="2" stroke-linecap="round" d="M8 7V3m8 4V3M5 11h14M5 21h14V11H5v10z" />
                                        </svg>
                                    </span>
                                    <span class="sidebar-copy min-w-0 flex-1 truncate">{{ __('Agenda') }}</span>
                                    <svg class="sidebar-chevron h-4 w-4 transition" :class="{ 'rotate-90': $store.nexus.menu.agenda, 'text-white/90': $store.nexus.isActivePrefix(['/agenda','/reports']), 'text-[rgb(var(--sidebar-muted)/1)]': !$store.nexus.isActivePrefix(['/agenda','/reports']) }" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-width="2" stroke-linecap="round" d="M9 6l6 6-6 6" />
                                    </svg>
                                </button>

                                <div x-show="$store.nexus.menu.agenda || $store.nexus.isActivePrefix(['/agenda','/reports'])" x-transition class="sidebar-branch mt-1 space-y-1 pl-2">
                                    @auth
                                        @can('viewAny', \App\Models\Event::class)
                                            <a href="{{ route('agenda.events.index') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm" :class="$store.nexus.isActivePrefix('/agenda/events') ? 'bg-primary-600 text-white shadow-sm ring-1 ring-primary-500/40' : ($store.nexus.theme === 'dark' ? 'text-[rgb(var(--sidebar-muted)/1)] hover:bg-white/10' : 'text-[rgb(var(--sidebar-muted)/1)] hover:bg-slate-100')" :aria-current="$store.nexus.isActivePrefix('/agenda/events') ? 'page' : null">
                                                <svg class="h-4 w-4 shrink-0 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="2" stroke-linecap="round" d="M8 7V3m8 4V3M3 11h18M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z"/></svg>
                                                <span>{{ __('Eventos') }}</span>
                                            </a>
                                        @endcan
                                        @can('viewAny', \App\Models\PublicGroup::class)
                                            <div>
                                                <button
                                                    type="button"
                                                    class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm font-medium"
                                                    :class="$store.nexus.isActivePrefix('/agenda/public-catalog') ? 'bg-primary-600 text-white shadow-sm ring-1 ring-primary-500/40' : ($store.nexus.theme === 'dark' ? 'text-[rgb(var(--sidebar-muted)/1)] hover:bg-white/10' : 'text-[rgb(var(--sidebar-muted)/1)] hover:bg-slate-100')"
                                                    @click="$store.nexus.toggleMenu('catalog')"
                                                    :aria-expanded="$store.nexus.menu.catalog"
                                                >
                                                    <span class="flex items-center gap-2">
                                                        <svg class="h-4 w-4 shrink-0 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="2" stroke-linecap="round" d="M4 19.5A2.5 2.5 0 016.5 17H20M4 19.5A2.5 2.5 0 016.5 22H20V2H6.5A2.5 2.5 0 004 4.5v15z"/></svg>
                                                        {{ __('Catálogo público') }}
                                                    </span>
                                                    <svg class="h-3.5 w-3.5 shrink-0 transition" :class="{ 'rotate-90': $store.nexus.menu.catalog }" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="2" stroke-linecap="round" d="M9 6l6 6-6 6" /></svg>
                                                </button>
                                                <div x-show="$store.nexus.menu.catalog" x-transition class="mt-0.5 space-y-0.5 pl-3">
                                                    <a href="{{ route('agenda.public-catalog.administrative') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="flex items-center gap-2 rounded-lg px-3 py-1.5 text-xs" :class="$store.nexus.isActivePrefix('/agenda/public-catalog/administrative') ? 'bg-primary-600 text-white shadow-sm ring-1 ring-primary-500/40' : ($store.nexus.theme === 'dark' ? 'text-[rgb(var(--sidebar-muted)/1)] hover:bg-white/10' : 'text-[rgb(var(--sidebar-muted)/1)] hover:bg-slate-100')" :aria-current="$store.nexus.isActivePrefix('/agenda/public-catalog/administrative') ? 'page' : null">
                                                        <svg class="h-3.5 w-3.5 shrink-0 opacity-60" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="2" stroke-linecap="round" d="M4 6h16M4 12h10M4 18h16"/></svg>
                                                        <span>{{ __('Estrutura administrativa') }}</span>
                                                    </a>
                                                    <a href="{{ route('agenda.public-groups.index') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="flex items-center gap-2 rounded-lg px-3 py-1.5 text-xs" :class="$store.nexus.isActivePrefix('/agenda/public-catalog/groups') ? 'bg-primary-600 text-white shadow-sm ring-1 ring-primary-500/40' : ($store.nexus.theme === 'dark' ? 'text-[rgb(var(--sidebar-muted)/1)] hover:bg-white/10' : 'text-[rgb(var(--sidebar-muted)/1)] hover:bg-slate-100')" :aria-current="$store.nexus.isActivePrefix('/agenda/public-catalog/groups') ? 'page' : null">
                                                        <svg class="h-3.5 w-3.5 shrink-0 opacity-60" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="2" stroke-linecap="round" d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/></svg>
                                                        <span>{{ __('Grupos') }}</span>
                                                    </a>
                                                    <a href="{{ route('agenda.public-departments.index') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="flex items-center gap-2 rounded-lg px-3 py-1.5 text-xs" :class="$store.nexus.isActivePrefix('/agenda/public-catalog/departments') ? 'bg-primary-600 text-white shadow-sm ring-1 ring-primary-500/40' : ($store.nexus.theme === 'dark' ? 'text-[rgb(var(--sidebar-muted)/1)] hover:bg-white/10' : 'text-[rgb(var(--sidebar-muted)/1)] hover:bg-slate-100')" :aria-current="$store.nexus.isActivePrefix('/agenda/public-catalog/departments') ? 'page' : null">
                                                        <svg class="h-3.5 w-3.5 shrink-0 opacity-60" viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="2" y="7" width="20" height="14" rx="2" stroke-width="2"/><path stroke-width="2" stroke-linecap="round" d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/></svg>
                                                        <span>{{ __('Departamentos') }}</span>
                                                    </a>
                                                    <a href="{{ route('agenda.public-positions.index') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="flex items-center gap-2 rounded-lg px-3 py-1.5 text-xs" :class="$store.nexus.isActivePrefix('/agenda/public-catalog/positions') ? 'bg-primary-600 text-white shadow-sm ring-1 ring-primary-500/40' : ($store.nexus.theme === 'dark' ? 'text-[rgb(var(--sidebar-muted)/1)] hover:bg-white/10' : 'text-[rgb(var(--sidebar-muted)/1)] hover:bg-slate-100')" :aria-current="$store.nexus.isActivePrefix('/agenda/public-catalog/positions') ? 'page' : null">
                                                        <svg class="h-3.5 w-3.5 shrink-0 opacity-60" viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="3" width="18" height="18" rx="2" stroke-width="2"/><path stroke-width="2" stroke-linecap="round" d="M7 7h4v4H7zM7 14h10M7 17.5h6"/></svg>
                                                        <span>{{ __('Cargos') }}</span>
                                                    </a>
                                                </div>
                                            </div>
                                        @endcan
                                        @can('viewAny', \App\Models\Audience::class)
                                            <a href="{{ route('agenda.audiences.index') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm" :class="$store.nexus.isActivePrefix('/agenda/audiences') ? 'bg-primary-600 text-white shadow-sm ring-1 ring-primary-500/40' : ($store.nexus.theme === 'dark' ? 'text-[rgb(var(--sidebar-muted)/1)] hover:bg-white/10' : 'text-[rgb(var(--sidebar-muted)/1)] hover:bg-slate-100')" :aria-current="$store.nexus.isActivePrefix('/agenda/audiences') ? 'page' : null">
                                                <svg class="h-4 w-4 shrink-0 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="2" stroke-linecap="round" d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8z"/></svg>
                                                <span>{{ __('Audiências') }}</span>
                                            </a>
                                        @endcan
                                        @can('viewAny', \App\Models\Approval::class)
                                            <a href="{{ route('agenda.approvals.index') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm" :class="$store.nexus.isActivePrefix('/agenda/approvals') ? 'bg-primary-600 text-white shadow-sm ring-1 ring-primary-500/40' : ($store.nexus.theme === 'dark' ? 'text-[rgb(var(--sidebar-muted)/1)] hover:bg-white/10' : 'text-[rgb(var(--sidebar-muted)/1)] hover:bg-slate-100')" :aria-current="$store.nexus.isActivePrefix('/agenda/approvals') ? 'page' : null">
                                                <svg class="h-4 w-4 shrink-0 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="2" stroke-linecap="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                <span>{{ __('Aprovações') }}</span>
                                            </a>
                                        @endcan
                                        @can('gerenciar_avisos')
                                            <a href="{{ route('agenda.whatsapp.index') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm" :class="$store.nexus.isActivePrefix('/agenda/whatsapp') ? 'bg-primary-600 text-white shadow-sm ring-1 ring-primary-500/40' : ($store.nexus.theme === 'dark' ? 'text-[rgb(var(--sidebar-muted)/1)] hover:bg-white/10' : 'text-[rgb(var(--sidebar-muted)/1)] hover:bg-slate-100')" :aria-current="$store.nexus.isActivePrefix('/agenda/whatsapp') ? 'page' : null">
                                                <svg class="h-4 w-4 shrink-0 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="2" stroke-linecap="round" d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/></svg>
                                                <span>{{ __('Notificações WhatsApp') }}</span>
                                            </a>
                                            <a href="{{ route('agenda.whatsapp-templates.index') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm" :class="$store.nexus.isActivePrefix('/agenda/whatsapp/templates') ? 'bg-primary-600 text-white shadow-sm ring-1 ring-primary-500/40' : ($store.nexus.theme === 'dark' ? 'text-[rgb(var(--sidebar-muted)/1)] hover:bg-white/10' : 'text-[rgb(var(--sidebar-muted)/1)] hover:bg-slate-100')">
                                                <span>{{ __('Templates WhatsApp') }}</span>
                                            </a>
                                            <a href="{{ route('agenda.event-type-templates') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm" :class="$store.nexus.isActivePrefix('/agenda/modelos-de-evento') ? 'bg-primary-600 text-white shadow-sm ring-1 ring-primary-500/40' : ($store.nexus.theme === 'dark' ? 'text-[rgb(var(--sidebar-muted)/1)] hover:bg-white/10' : 'text-[rgb(var(--sidebar-muted)/1)] hover:bg-slate-100')">
                                                <span>{{ __('Modelos dos tipos') }}</span>
                                            </a>
                                        @endcan
                                        @can('viewAny', \App\Models\PublicGroup::class)
                                            <a href="{{ route('agenda.public-catalog.operational-services') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="flex min-h-11 items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-slate-100 dark:hover:bg-white/10" :aria-current="$store.nexus.isActivePrefix('/agenda/public-catalog/operational-services') ? 'page' : null">{{ __('Eixo serviços') }}</a>
                                            <a href="{{ route('agenda.public-catalog.ministry') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="flex min-h-11 items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-slate-100 dark:hover:bg-white/10" :aria-current="$store.nexus.isActivePrefix('/agenda/public-catalog/ministry') ? 'page' : null">{{ __('Eixo ministério') }}</a>
                                            <a href="{{ route('agenda.public-catalog.music') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="flex min-h-11 items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-slate-100 dark:hover:bg-white/10" :aria-current="$store.nexus.isActivePrefix('/agenda/public-catalog/music') ? 'page' : null">{{ __('Eixo música') }}</a>
                                        @endcan
                                    @endauth
                                </div>
                            </div>

                            {{-- Secretaria --}}
                            @auth
                                @php
                                    $u = auth()->user();
                                    $canSecretariaReports = $u->isSuperAdmin() || $u->can('visualizar_relatorios');
                                    $canSecretariaEdit = $u->isSuperAdmin() || $u->can('editar_evento');
                                    $canSecretariaWa = $u->isSuperAdmin() || $u->can('gerenciar_avisos');
                                    $showSecretaria = $canSecretariaReports || $canSecretariaEdit || $canSecretariaWa;
                                @endphp
                                @if ($showSecretaria)
                                    <div class="space-y-1">
                                        <button
                                            type="button"
                                            class="sidebar-root flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left text-sm font-semibold transition"
                                            :class="$store.nexus.isActivePrefix('/secretaria') ? 'bg-primary-600 text-white shadow-sm ring-1 ring-primary-500/40' : ($store.nexus.theme === 'dark' ? 'bg-slate-900/50 shadow-sm ring-1 ring-slate-800/70 text-[rgb(var(--sidebar-text)/1)] hover:bg-white/10' : 'bg-white shadow-sm ring-1 ring-slate-200/70 text-[rgb(var(--sidebar-text)/1)] hover:bg-slate-50')"
                                            title="{{ __('Secretaria') }}"
                                            @click="$store.nexus.toggleMenu('secretaria')"
                                            :aria-expanded="!!$store.nexus.menu.secretaria || $store.nexus.isActivePrefix('/secretaria')"
                                        >
                                            <span class="grid h-9 w-9 place-items-center rounded-lg" :class="$store.nexus.isActivePrefix('/secretaria') ? 'bg-white/15 text-white' : ($store.nexus.theme === 'dark' ? 'bg-white/10' : 'bg-slate-50 ring-1 ring-slate-200/60')">
                                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="2" stroke-linecap="round" d="M9 12h6m-6 4h6M7 4h10a2 2 0 012 2v14l-4-2-4 2-4-2-4 2V6a2 2 0 012-2z"/></svg>
                                            </span>
                                            <span class="sidebar-copy min-w-0 flex-1 truncate">{{ __('Secretaria') }}</span>
                                            <svg class="sidebar-chevron h-4 w-4 transition" :class="{ 'rotate-90': !!$store.nexus.menu.secretaria || $store.nexus.isActivePrefix('/secretaria') }" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="2" stroke-linecap="round" d="M9 6l6 6-6 6" /></svg>
                                        </button>
                                        <div x-show="!!$store.nexus.menu.secretaria || $store.nexus.isActivePrefix('/secretaria')" x-cloak x-transition class="sidebar-branch mt-1 space-y-1 pl-2">
                                            @if ($canSecretariaReports)
                                                <a href="{{ route('secretaria.baptism-list') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="flex min-h-11 items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-slate-100 dark:hover:bg-white/10" :aria-current="$store.nexus.isActivePrefix('/secretaria/lista-batismos') ? 'page' : null">{{ __('Lista de Batismos e Diversos') }}</a>
                                                <a href="{{ route('secretaria.markings') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="flex min-h-11 items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-slate-100 dark:hover:bg-white/10" :aria-current="$store.nexus.isActivePrefix('/secretaria/marcacoes') ? 'page' : null">{{ __('Relatório de Marcações') }}</a>
                                                <a href="{{ route('secretaria.quem-atende') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="flex min-h-11 items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-slate-100 dark:hover:bg-white/10" :aria-current="$store.nexus.isActivePrefix('/secretaria/quem-atende') ? 'page' : null">{{ __('Quem atende') }}</a>
                                                <a href="{{ route('reports.events.csv') }}" @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="flex min-h-11 items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-slate-100 dark:hover:bg-white/10">{{ __('Exportar eventos (CSV)') }}</a>
                                            @endif
                                            @if ($canSecretariaEdit)
                                                <a href="{{ route('secretaria.notices') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="flex min-h-11 items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-slate-100 dark:hover:bg-white/10" :aria-current="$store.nexus.isActivePrefix('/secretaria/avisos') ? 'page' : null">{{ __('Avisos do boletim') }}</a>
                                                <a href="{{ route('secretaria.schedule-changes') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="flex min-h-11 items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-slate-100 dark:hover:bg-white/10" :aria-current="$store.nexus.isActivePrefix('/secretaria/alteracoes') ? 'page' : null">{{ __('Alterações de culto') }}</a>
                                                <a href="{{ route('secretaria.collections') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="flex min-h-11 items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-slate-100 dark:hover:bg-white/10" :aria-current="$store.nexus.isActivePrefix('/secretaria/coletas') ? 'page' : null">{{ __('Coletas especiais') }}</a>
                                            @endif
                                            @if ($canSecretariaWa)
                                                <a href="{{ route('secretaria.whatsapp-groups') }}" wire:navigate @click="$store.nexus.ensureSidebarExpandedForNavigation()" class="flex min-h-11 items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-slate-100 dark:hover:bg-white/10" :aria-current="$store.nexus.isActivePrefix('/secretaria/grupos-whatsapp') ? 'page' : null">{{ __('Grupos WhatsApp') }}</a>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            @endauth
                        </div>
                    </div>
                </nav>

                @auth
                    <livewire:shell.sidebar-density-toggle />
                @endauth
            </aside>

            <!-- Main -->
            <div class="nexus-main min-h-screen min-w-0 transition-[padding] duration-200 lg:pl-72" :class="{ 'is-rail-layout': $store.nexus.sidebarDensity === 'icons' }">
                <!-- Topbar -->
                <header
                    class="sticky top-0 z-30 border-b backdrop-blur"
                    style="background-color: rgb(var(--topbar-bg) / 0.80); border-color: rgb(var(--topbar-border) / 0.80); color: rgb(var(--topbar-text) / 1);"
                >
                    <div class="mx-auto flex h-16 w-full max-w-full items-center gap-3 px-4 sm:px-6 lg:px-8">
                        <button
                            type="button"
                            class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg text-[rgb(var(--topbar-text)/1)] hover:bg-[rgb(var(--topbar-muted)/0.14)] lg:hidden"
                            @click="$store.nexus.toggleSidebar()"
                            aria-label="{{ __('Abrir menu') }}"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-width="2" stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>

                        <button
                            type="button"
                            class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg text-[rgb(var(--topbar-text)/1)] hover:bg-[rgb(var(--topbar-muted)/0.14)] md:hidden"
                            aria-label="{{ __('Buscar páginas e ações') }}"
                            onclick="window.dispatchEvent(new KeyboardEvent('keydown', { key: 'k', ctrlKey: true, bubbles: true }))"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-width="2" stroke-linecap="round" d="M21 21l-4.3-4.3M11 18a7 7 0 100-14 7 7 0 000 14z" /></svg>
                        </button>

                        <div class="hidden min-w-0 flex-1 md:block">
                            <button
                                type="button"
                                class="flex min-h-11 w-full items-center gap-2 rounded-xl border px-3 py-2 text-left text-sm shadow-sm"
                                style="border-color: rgb(var(--topbar-border) / 0.90); background-color: rgb(var(--topbar-bg) / 0.65); color: rgb(var(--topbar-muted) / 1);"
                                aria-label="{{ __('Buscar páginas e ações') }}"
                                onclick="window.dispatchEvent(new KeyboardEvent('keydown', { key: 'k', ctrlKey: true, bubbles: true }))"
                            >
                                <svg class="h-5 w-5 shrink-0 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="2" stroke-linecap="round" d="M21 21l-4.3-4.3M11 18a7 7 0 100-14 7 7 0 000 14z" /></svg>
                                <span class="truncate">{{ __('Buscar ou comando…') }}</span>
                                <kbd class="ml-auto hidden rounded border px-1.5 py-0.5 text-[10px] font-semibold lg:inline" style="border-color: rgb(var(--topbar-border) / 0.9);">Ctrl+K</kbd>
                            </button>
                        </div>

                        <div class="ml-auto flex items-center gap-2">
                            <div class="relative" x-data="{ open: false }">
                                <button
                                    type="button"
                                    class="inline-flex min-h-11 items-center gap-2 rounded-xl border px-3 text-sm font-semibold text-[rgb(var(--topbar-text)_/_1)] hover:bg-[rgb(var(--topbar-muted)_/_0.12)]"
                                    style="border-color: rgb(var(--topbar-border) / 0.90); background-color: rgb(var(--topbar-bg) / 0.55);"
                                    @click="open = !open"
                                    :aria-expanded="open"
                                    aria-haspopup="true"
                                    aria-label="{{ __('Aparência') }}"
                                >
                                    <svg class="h-4 w-4 shrink-0 opacity-90" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                        <path stroke-width="2" stroke-linecap="round" d="M12 3a6 6 0 100 12 6 6 0 000-12z" />
                                        <path stroke-width="2" stroke-linecap="round" d="M12 3v2M12 19v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M3 12h2M19 12h2" />
                                    </svg>
                                    <span class="hidden max-w-[7rem] truncate sm:inline">{{ __('Aparência') }}</span>
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
                                        <div class="px-1 pb-1.5 text-[10px] font-semibold uppercase tracking-wide" style="color: rgb(var(--topbar-muted) / 1);">{{ __('Tema') }}</div>
                                        <div class="flex rounded-lg border p-0.5" style="border-color: rgb(var(--topbar-border) / 0.85); background-color: rgb(var(--topbar-bg) / 0.5);">
                                            <button
                                                type="button"
                                                role="menuitem"
                                                class="flex-1 rounded-md px-2 py-2 text-center text-xs font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 focus-visible:ring-offset-[rgb(var(--topbar-bg)_/_1)] dark:focus-visible:ring-offset-slate-900"
                                                :class="$store.nexus.theme === 'light' ? 'bg-primary-600 text-white shadow-sm' : 'text-[rgb(var(--topbar-text)_/_1)] hover:bg-[rgb(var(--topbar-muted)_/_0.14)]'"
                                                @click="$store.nexus.setTheme('light'); open = false"
                                            >{{ __('Claro') }}</button>
                                            <button
                                                type="button"
                                                role="menuitem"
                                                class="flex-1 rounded-md px-2 py-2 text-center text-xs font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 focus-visible:ring-offset-[rgb(var(--topbar-bg)_/_1)] dark:focus-visible:ring-offset-slate-900"
                                                :class="$store.nexus.theme === 'dark' ? 'bg-primary-600 text-white shadow-sm' : 'text-[rgb(var(--topbar-text)_/_1)] hover:bg-[rgb(var(--topbar-muted)_/_0.14)]'"
                                                @click="$store.nexus.setTheme('dark'); open = false"
                                            >{{ __('Escuro') }}</button>
                                        </div>
                                        <div class="my-2 border-t" style="border-color: rgb(var(--topbar-border) / 0.65);"></div>
                                        <div class="px-1 pb-1.5 text-[10px] font-semibold uppercase tracking-wide" style="color: rgb(var(--topbar-muted) / 1);">{{ __('Cor primária') }}</div>
                                        <div class="grid grid-cols-2 gap-1">
                                            <button type="button" role="menuitem" class="rounded-lg px-2 py-2 text-center text-xs font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 focus-visible:ring-offset-[rgb(var(--topbar-bg)_/_1)] dark:focus-visible:ring-offset-slate-900" :class="$store.nexus.palette === 'blue' ? 'bg-primary-600 text-white shadow-sm' : 'text-[rgb(var(--topbar-text)_/_1)] hover:bg-[rgb(var(--topbar-muted)_/_0.14)]'" @click="$store.nexus.setPalette('blue'); open = false">{{ __('Azul') }}</button>
                                            <button type="button" role="menuitem" class="rounded-lg px-2 py-2 text-center text-xs font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 focus-visible:ring-offset-[rgb(var(--topbar-bg)_/_1)] dark:focus-visible:ring-offset-slate-900" :class="$store.nexus.palette === 'navy' ? 'bg-primary-600 text-white shadow-sm' : 'text-[rgb(var(--topbar-text)_/_1)] hover:bg-[rgb(var(--topbar-muted)_/_0.14)]'" @click="$store.nexus.setPalette('navy'); open = false">{{ __('Azul marinho') }}</button>
                                            <button type="button" role="menuitem" class="rounded-lg px-2 py-2 text-center text-xs font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 focus-visible:ring-offset-[rgb(var(--topbar-bg)_/_1)] dark:focus-visible:ring-offset-slate-900" :class="$store.nexus.palette === 'green' ? 'bg-primary-600 text-white shadow-sm' : 'text-[rgb(var(--topbar-text)_/_1)] hover:bg-[rgb(var(--topbar-muted)_/_0.14)]'" @click="$store.nexus.setPalette('green'); open = false">{{ __('Verde') }}</button>
                                            <button type="button" role="menuitem" class="rounded-lg px-2 py-2 text-center text-xs font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 focus-visible:ring-offset-[rgb(var(--topbar-bg)_/_1)] dark:focus-visible:ring-offset-slate-900" :class="$store.nexus.palette === 'green_dark' ? 'bg-primary-600 text-white shadow-sm' : 'text-[rgb(var(--topbar-text)_/_1)] hover:bg-[rgb(var(--topbar-muted)_/_0.14)]'" @click="$store.nexus.setPalette('green_dark'); open = false">{{ __('Verde escuro') }}</button>
                                            <button type="button" role="menuitem" class="rounded-lg px-2 py-2 text-center text-xs font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 focus-visible:ring-offset-[rgb(var(--topbar-bg)_/_1)] dark:focus-visible:ring-offset-slate-900" :class="$store.nexus.palette === 'red' ? 'bg-primary-600 text-white shadow-sm' : 'text-[rgb(var(--topbar-text)_/_1)] hover:bg-[rgb(var(--topbar-muted)_/_0.14)]'" @click="$store.nexus.setPalette('red'); open = false">{{ __('Vermelho') }}</button>
                                            <button type="button" role="menuitem" class="rounded-lg px-2 py-2 text-center text-xs font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 focus-visible:ring-offset-[rgb(var(--topbar-bg)_/_1)] dark:focus-visible:ring-offset-slate-900" :class="$store.nexus.palette === 'red_dark' ? 'bg-primary-600 text-white shadow-sm' : 'text-[rgb(var(--topbar-text)_/_1)] hover:bg-[rgb(var(--topbar-muted)_/_0.14)]'" @click="$store.nexus.setPalette('red_dark'); open = false">{{ __('Vermelho escuro') }}</button>
                                            <button type="button" role="menuitem" class="rounded-lg px-2 py-2 text-center text-xs font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 focus-visible:ring-offset-[rgb(var(--topbar-bg)_/_1)] dark:focus-visible:ring-offset-slate-900" :class="$store.nexus.palette === 'orange' ? 'bg-primary-600 text-white shadow-sm' : 'text-[rgb(var(--topbar-text)_/_1)] hover:bg-[rgb(var(--topbar-muted)_/_0.14)]'" @click="$store.nexus.setPalette('orange'); open = false">{{ __('Laranja') }}</button>
                                            <button type="button" role="menuitem" class="rounded-lg px-2 py-2 text-center text-xs font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 focus-visible:ring-offset-[rgb(var(--topbar-bg)_/_1)] dark:focus-visible:ring-offset-slate-900" :class="$store.nexus.palette === 'brown' ? 'bg-primary-600 text-white shadow-sm' : 'text-[rgb(var(--topbar-text)_/_1)] hover:bg-[rgb(var(--topbar-muted)_/_0.14)]'" @click="$store.nexus.setPalette('brown'); open = false">{{ __('Marrom') }}</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button
                                type="button"
                                class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg text-[rgb(var(--topbar-text)/1)] hover:bg-[rgb(var(--topbar-muted)/0.14)]"
                                @click="$store.nexus.toggleTheme()"
                                :aria-label="$store.nexus.theme === 'dark' ? 'Usar tema claro' : 'Usar tema escuro'"
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
                                <button type="button" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg text-[rgb(var(--topbar-text)/1)] hover:bg-[rgb(var(--topbar-muted)/0.14)]" @click="open=!open" aria-label="{{ __('Notificações') }}" :aria-expanded="open" aria-haspopup="true">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                        <path stroke-width="2" stroke-linecap="round" d="M15 17h5l-1.4-1.4M5 7a7 7 0 0114 0v1a5 5 0 01-1 3.3V17H6v-5.7A5 5 0 015 8V7z" />
                                    </svg>
                                </button>
                                <div
                                    x-cloak
                                    x-show="open"
                                    @click.outside="open=false"
                                    @keydown.escape.window="open=false"
                                    class="absolute right-0 z-[60] mt-2 w-72 rounded-xl border p-3 text-sm shadow-lg"
                                    style="border-color: rgb(var(--topbar-border) / 0.90); background-color: rgb(var(--topbar-bg) / 1); color: rgb(var(--topbar-text) / 1);"
                                >
                                    <div class="font-semibold">{{ __('Notificações') }}</div>
                                    <div class="mt-2" style="color: rgb(var(--topbar-muted) / 1);">Nenhum aviso por enquanto.</div>
                                </div>
                            </div>

                            <div class="relative" x-data="{ open:false }">
                                <button
                                    type="button"
                                    class="inline-flex min-h-11 items-center gap-2 rounded-xl border px-2 text-sm hover:bg-[rgb(var(--topbar-muted)/0.10)]"
                                    style="border-color: rgb(var(--topbar-border) / 0.90); background-color: rgb(var(--topbar-bg) / 0.55); color: rgb(var(--topbar-text) / 1);"
                                    @click="open=!open"
                                    aria-label="{{ __('Conta de :name', ['name' => auth()->user()->name]) }}"
                                    :aria-expanded="open"
                                    aria-haspopup="menu"
                                >
                                    <span
                                        class="relative grid h-8 w-8 shrink-0 place-items-center overflow-hidden rounded-full bg-primary-600 text-xs font-bold text-white ring-1 ring-[rgb(var(--topbar-border)/0.5)]"
                                        x-data="{ url: @js(auth()->user()?->profilePhotoUrl()), initials: @js(auth()->user()?->profileInitials() ?? 'U') }"
                                        @profile-photo-updated.window="url = ($event.detail && $event.detail.url !== undefined) ? $event.detail.url : ($event.detail[0] ?? null)"
                                    >
                                        <img x-show="url" x-bind:src="url" alt="" class="absolute inset-0 h-full w-full object-cover" x-cloak />
                                        <span x-show="!url" x-text="initials" x-cloak></span>
                                    </span>
                                    <span class="hidden max-w-[10rem] truncate sm:block">{{ auth()->user()->name }}</span>
                                    <svg class="h-4 w-4" style="color: rgb(var(--topbar-muted) / 1);" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-width="2" stroke-linecap="round" d="M6 9l6 6 6-6" />
                                    </svg>
                                </button>
                                <div
                                    x-cloak
                                    x-show="open"
                                    @click.outside="open=false"
                                    @keydown.escape.window="open=false"
                                    class="absolute right-0 z-[60] mt-2 w-56 rounded-xl border py-1 text-sm shadow-lg"
                                    style="border-color: rgb(var(--topbar-border) / 0.90); background-color: rgb(var(--topbar-bg) / 1); color: rgb(var(--topbar-text) / 1);"
                                    role="menu"
                                >
                                    <a href="{{ route('profile') }}" wire:navigate @click="open=false" class="flex min-h-11 items-center px-3 hover:bg-[rgb(var(--topbar-muted)/0.10)]" role="menuitem">{{ __('Perfil') }}</a>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="flex min-h-11 w-full items-center px-3 text-left hover:bg-[rgb(var(--topbar-muted)/0.10)]">{{ __('Sair') }}</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    @auth
                        <div class="border-t px-4 py-2 sm:px-6 lg:px-8" style="border-color: rgb(var(--topbar-border) / 0.80); background-color: rgb(var(--topbar-bg) / 0.55);">
                            <div class="mx-auto w-full max-w-full">
                                <livewire:context.organizational-context-selector :compact="true" />
                            </div>
                        </div>
                    @endauth

                    @isset($header)
                        <div class="border-t px-4 py-4 sm:px-6 lg:px-8" style="border-color: rgb(var(--topbar-border) / 0.80); background-color: rgb(var(--topbar-bg) / 0.55); color: rgb(var(--topbar-text) / 1);">
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

                <footer class="mx-auto w-full max-w-full px-4 pb-10 pt-6 text-center text-xs text-slate-600 dark:text-slate-300 sm:px-6 lg:px-8">
                    @php($inicio = 2026)
                    <p>© {{ now()->year > $inicio ? $inicio.'–'.now()->year : $inicio }} CCBNexus <span aria-hidden="true">|</span> Todos os direitos reservados. Desenvolvido por Thomas Gonçalves</p>
                </footer>
            </div>
        </div>

        @auth
            <x-nexus.command-palette />
        @endauth

        <style>
            [x-cloak] { display: none !important; }
            aside nav a,
            aside nav button { min-height: 2.75rem; }
        </style>
        @stack('scripts')
    </body>
</html>
