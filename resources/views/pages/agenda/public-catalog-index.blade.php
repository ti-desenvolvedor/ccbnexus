<x-app-layout>
    <x-slot name="header">
        <div>
            <nav class="text-xs text-slate-500 dark:text-slate-400">
                <span>{{ __('Agenda') }}</span>
                <span class="mx-1">/</span>
                <span class="text-slate-700 dark:text-slate-300">{{ __('Catálogo público') }}</span>
            </nav>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">{{ __('Catálogo público') }}</h1>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ __('Organograma canónico: administrativo, operacional, ministério e música.') }}</p>
        </div>
    </x-slot>

    <div class="grid gap-4 lg:grid-cols-2">
        <a href="{{ route('agenda.public-catalog.administrative') }}" wire:navigate class="block rounded-2xl border-2 border-primary-200 bg-gradient-to-br from-primary-50 to-white p-6 shadow-sm transition hover:border-primary-400 dark:border-primary-800 dark:from-primary-950/40 dark:to-slate-950">
            <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">{{ __('Estrutura administrativa (Grupos I–V)') }}</h2>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ __('17 departamentos em âmbito regional e administração.') }}</p>
            <span class="mt-4 inline-flex rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white">{{ __('Explorar') }} →</span>
        </a>
        <a href="{{ route('agenda.public-catalog.operational-services') }}" wire:navigate class="block rounded-2xl border-2 border-emerald-200 bg-gradient-to-br from-emerald-50 to-white p-6 shadow-sm transition hover:border-emerald-400 dark:border-emerald-900 dark:from-emerald-950/40 dark:to-slate-950">
            <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">{{ __('Serviços operacionais unificados') }}</h2>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ __('Sete departamentos em regional, administração e casa.') }}</p>
            <span class="mt-4 inline-flex rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">{{ __('Explorar') }} →</span>
        </a>
        <a href="{{ route('agenda.public-catalog.ministry') }}" wire:navigate class="block rounded-2xl border-2 border-violet-200 bg-gradient-to-br from-violet-50 to-white p-6 shadow-sm transition hover:border-violet-400 dark:border-violet-900 dark:from-violet-950/40 dark:to-slate-950">
            <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">{{ __('Ministério') }}</h2>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ __('Ancião, diácono e cooperadores — regional e por administração.') }}</p>
            <span class="mt-4 inline-flex rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white">{{ __('Explorar') }} →</span>
        </a>
        <a href="{{ route('agenda.public-catalog.music') }}" wire:navigate class="block rounded-2xl border-2 border-amber-200 bg-gradient-to-br from-amber-50 to-white p-6 shadow-sm transition hover:border-amber-400 dark:border-amber-900 dark:from-amber-950/40 dark:to-slate-950">
            <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">{{ __('Música') }}</h2>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ __('Carteiras regionais, encarregados e cargos locais.') }}</p>
            <span class="mt-4 inline-flex rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white">{{ __('Explorar') }} →</span>
        </a>
    </div>

    <h2 class="mb-3 mt-10 text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('Manutenção do catálogo') }}</h2>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.card title="{{ __('Grupos') }}">
            <p class="text-sm text-slate-600 dark:text-slate-300">{{ __('Quatro eixos temáticos por regional.') }}</p>
            <a href="{{ route('agenda.public-groups.index') }}" wire:navigate class="mt-3 inline-block text-sm font-semibold text-primary-600 hover:underline">{{ __('Abrir') }} →</a>
        </x-ui.card>
        <x-ui.card title="{{ __('Departamentos') }}">
            <p class="text-sm text-slate-600 dark:text-slate-300">{{ __('Listagem com filtros por âmbito e eixo.') }}</p>
            <a href="{{ route('agenda.public-departments.index') }}" wire:navigate class="mt-3 inline-block text-sm font-semibold text-primary-600 hover:underline">{{ __('Abrir') }} →</a>
        </x-ui.card>
        <x-ui.card title="{{ __('Cargos') }}">
            <p class="text-sm text-slate-600 dark:text-slate-300">{{ __('Funções ligadas a departamentos.') }}</p>
            <a href="{{ route('agenda.public-positions.index') }}" wire:navigate class="mt-3 inline-block text-sm font-semibold text-primary-600 hover:underline">{{ __('Abrir') }} →</a>
        </x-ui.card>
        <x-ui.card title="{{ __('Audiências legado') }}">
            <p class="text-sm text-slate-600 dark:text-slate-300">{{ __('Lista plana histórica.') }}</p>
            <a href="{{ route('agenda.audiences.index') }}" wire:navigate class="mt-3 inline-block text-sm font-semibold text-primary-600 hover:underline">{{ __('Abrir') }} →</a>
        </x-ui.card>
    </div>
</x-app-layout>
