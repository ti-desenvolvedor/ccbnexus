@php
    $user = auth()->user();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-primary-700 dark:text-primary-300">{{ __('Conta') }}</div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100 sm:text-3xl">{{ __('Perfil') }}</h1>
                <p class="mt-1 max-w-3xl text-sm text-slate-600 dark:text-slate-300">
                    {{ __('Gerir foto, dados pessoais, preferências, contexto organizacional e segurança da conta.') }}
                </p>
            </div>
        </div>
    </x-slot>

    <div class="w-full space-y-8">
        {{-- Hero full width --}}
        <section class="relative overflow-hidden rounded-3xl border border-slate-200/80 bg-gradient-to-br from-primary-600 via-primary-700 to-slate-900 shadow-xl dark:border-slate-800">
            <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-white/10 blur-3xl" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -bottom-32 left-1/4 h-64 w-64 rounded-full bg-primary-400/20 blur-3xl" aria-hidden="true"></div>

            <div class="relative flex flex-col gap-8 p-6 sm:p-8 lg:flex-row lg:items-center lg:gap-12 lg:p-10 xl:p-12">
                <livewire:profile.update-profile-photo />

                <div class="min-w-0 flex-1 text-white">
                    <p class="text-sm font-medium text-primary-100">{{ __('A sua conta') }}</p>
                    <h2 class="mt-1 truncate text-2xl font-bold tracking-tight sm:text-3xl">{{ $user?->name }}</h2>
                    <p class="mt-1 truncate text-primary-100/90">{{ $user?->email }}</p>
                    @if ($user?->phone)
                        <p class="mt-0.5 text-sm text-primary-100/80">{{ $user->phone }}</p>
                    @endif

                    <div class="mt-4 flex flex-wrap gap-2">
                        @if ($user?->hasVerifiedEmail())
                            <span class="inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-xs font-medium text-white ring-1 ring-white/20">
                                {{ __('E-mail verificado') }}
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-amber-400/20 px-3 py-1 text-xs font-medium text-amber-100 ring-1 ring-amber-300/30">
                                {{ __('E-mail pendente') }}
                            </span>
                        @endif
                        @if ($user?->isSuperAdmin())
                            <span class="inline-flex items-center rounded-full bg-violet-400/20 px-3 py-1 text-xs font-medium text-violet-100 ring-1 ring-violet-300/30">
                                {{ __('Super administrador') }}
                            </span>
                        @endif
                        <span class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-white ring-1 ring-white/15">
                            {{ __(':count itens/página', ['count' => $user?->resolvedListPerPage() ?? 20]) }}
                        </span>
                    </div>
                </div>
            </div>
        </section>

        {{-- Conteúdo full width em grelha ampla --}}
        <div class="grid w-full gap-6 xl:grid-cols-12">
            <div class="space-y-6 xl:col-span-8">
                <x-ui.card>
                    <livewire:profile.update-profile-information-form />
                </x-ui.card>

                <x-ui.card>
                    <livewire:profile.update-account-preferences />
                </x-ui.card>

                <x-ui.card>
                    <livewire:profile.update-password-form />
                </x-ui.card>
            </div>

            <aside class="space-y-6 xl:col-span-4">
                <x-ui.card title="{{ __('Contexto organizacional') }}">
                    <p class="mb-4 text-sm text-slate-600 dark:text-slate-300">
                        {{ __('Regional, administração e casa usados nas listagens quando não há outro filtro activo.') }}
                    </p>
                    <livewire:context.organizational-context-selector />
                </x-ui.card>

                @if ($user?->can('gerenciar_usuarios') || $user?->can('moderar_conteudo') || $user?->isSuperAdmin())
                    <x-ui.card title="{{ __('Ferramentas de moderação') }}">
                        <p class="mb-4 text-sm text-slate-600 dark:text-slate-300">
                            {{ __('Atalhos conforme as suas permissões.') }}
                        </p>
                        <div class="flex flex-col gap-2">
                            @can('gerenciar_usuarios')
                                <x-ui.button variant="secondary" size="sm" href="{{ route('users.index') }}" wire:navigate class="min-h-11 justify-center">
                                    {{ __('Gerir utilizadores') }}
                                </x-ui.button>
                            @endcan
                            @can('viewAny', App\Models\Approval::class)
                                <x-ui.button variant="secondary" size="sm" href="{{ route('agenda.approvals.index') }}" wire:navigate class="min-h-11 justify-center">
                                    {{ __('Aprovações de agenda') }}
                                </x-ui.button>
                            @endcan
                            @can('gerenciar_avisos')
                                <x-ui.button variant="secondary" size="sm" href="{{ route('agenda.whatsapp.index') }}" wire:navigate class="min-h-11 justify-center">
                                    {{ __('Notificações WhatsApp') }}
                                </x-ui.button>
                            @endcan
                            @can('viewAny', App\Models\AccessRequest::class)
                                <x-ui.button variant="secondary" size="sm" href="{{ route('access.requests.index') }}" wire:navigate class="min-h-11 justify-center">
                                    {{ __('Pedidos de acesso') }}
                                </x-ui.button>
                            @endcan
                        </div>
                    </x-ui.card>
                @endif
            </aside>
        </div>

        <x-ui.card class="border-red-200/80 dark:border-red-900/50">
            <livewire:profile.delete-user-form />
        </x-ui.card>
    </div>
</x-app-layout>
