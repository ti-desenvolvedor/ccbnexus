<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-primary-700 dark:text-primary-300">CCB Nexus</div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">Painel</h1>
                <p class="mt-1 max-w-2xl text-sm text-slate-600 dark:text-slate-300">
                    Resumo da organização (regional, administração, casas de oração), agenda e infraestrutura no seu contexto ativo.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                @can('visualizar_relatorios')
                    <x-ui.button variant="secondary" size="sm" href="{{ route('reports.events.csv') }}">Exportar eventos (CSV)</x-ui.button>
                @endcan
                @can('create', App\Models\Event::class)
                    <x-ui.button size="sm" href="{{ route('agenda.events.create') }}">Novo evento</x-ui.button>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="mb-6">
        <livewire:context.organizational-context-selector />
    </div>

    <livewire:dashboard.metrics />

    <div class="mt-6 grid gap-4 lg:grid-cols-3">
        <x-ui.card class="lg:col-span-2" title="Atalhos">
            <p class="mb-4 text-sm text-slate-600 dark:text-slate-300">
                Módulos principais do sistema. Alguns exigem permissão ou contexto organizacional selecionado acima.
            </p>
            <div class="grid gap-3 sm:grid-cols-2">
                @can('viewAny', App\Models\Event::class)
                    <a href="{{ route('agenda.events.index') }}" class="rounded-xl border border-slate-200 bg-white p-4 text-sm font-semibold text-slate-900 shadow-sm transition hover:border-primary-300 hover:bg-primary-50/40 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100 dark:hover:border-primary-700 dark:hover:bg-primary-950/30">
                        Agenda — eventos
                    </a>
                @endcan
                @can('viewAny', App\Models\RoomReservation::class)
                    <a href="{{ route('infrastructure.room-reservations.index') }}" class="rounded-xl border border-slate-200 bg-white p-4 text-sm font-semibold text-slate-900 shadow-sm transition hover:border-primary-300 hover:bg-primary-50/40 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100 dark:hover:border-primary-700 dark:hover:bg-primary-950/30">
                        Reservas de salas
                    </a>
                @endcan
                <a href="{{ route('organization.regionals.index') }}" class="rounded-xl border border-slate-200 bg-white p-4 text-sm font-semibold text-slate-900 shadow-sm transition hover:border-primary-300 hover:bg-primary-50/40 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100 dark:hover:border-primary-700 dark:hover:bg-primary-950/30">
                    Organização — regionais
                </a>
                <a href="{{ route('organization.administrations.index') }}" class="rounded-xl border border-slate-200 bg-white p-4 text-sm font-semibold text-slate-900 shadow-sm transition hover:border-primary-300 hover:bg-primary-50/40 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100 dark:hover:border-primary-700 dark:hover:bg-primary-950/30">
                    Administrações
                </a>
                <a href="{{ route('organization.prayer-houses.index') }}" class="rounded-xl border border-slate-200 bg-white p-4 text-sm font-semibold text-slate-900 shadow-sm transition hover:border-primary-300 hover:bg-primary-50/40 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100 dark:hover:border-primary-700 dark:hover:bg-primary-950/30">
                    Casas de oração
                </a>
                @can('viewAny', App\Models\MeetingRoom::class)
                    <a href="{{ route('infrastructure.meeting-rooms.index') }}" class="rounded-xl border border-slate-200 bg-white p-4 text-sm font-semibold text-slate-900 shadow-sm transition hover:border-primary-300 hover:bg-primary-50/40 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100 dark:hover:border-primary-700 dark:hover:bg-primary-950/30">
                        Salas de reunião
                    </a>
                @endcan
                @can('viewAny', App\Models\User::class)
                    <a href="{{ route('users.index') }}" class="rounded-xl border border-slate-200 bg-white p-4 text-sm font-semibold text-slate-900 shadow-sm transition hover:border-primary-300 hover:bg-primary-50/40 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100 dark:hover:border-primary-700 dark:hover:bg-primary-950/30">
                        Utilizadores
                    </a>
                @endcan
                @can('aprovar_acesso')
                    <a href="{{ route('access.requests.index') }}" class="rounded-xl border border-slate-200 bg-white p-4 text-sm font-semibold text-slate-900 shadow-sm transition hover:border-primary-300 hover:bg-primary-50/40 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100 dark:hover:border-primary-700 dark:hover:bg-primary-950/30">
                        Pedidos de acesso
                    </a>
                @endcan
            </div>
        </x-ui.card>

        <x-ui.card title="Aprovações e governança">
            <p class="text-sm text-slate-600 dark:text-slate-300">
                Eventos e reservas podem exigir aprovação conforme as regras da sua regional. Utilize o fluxo de aprovações para rever pendências.
            </p>
            <div class="mt-4 space-y-2">
                @can('viewAny', App\Models\Event::class)
                    <x-ui.button class="w-full justify-center" variant="secondary" size="sm" href="{{ route('agenda.approvals.index') }}">
                        Aprovações de agenda
                    </x-ui.button>
                @endcan
                @can('viewAny', App\Models\RoomReservation::class)
                    <x-ui.button class="w-full justify-center" variant="secondary" size="sm" href="{{ route('infrastructure.room-reservations.index') }}">
                        Rever reservas
                    </x-ui.button>
                @endcan
            </div>
        </x-ui.card>
    </div>
</x-app-layout>
