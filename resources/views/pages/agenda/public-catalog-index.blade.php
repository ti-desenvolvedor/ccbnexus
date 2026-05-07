<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="text-xs font-semibold uppercase tracking-wide text-primary-700 dark:text-primary-300">{{ __('Agenda') }}</div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">{{ __('Catálogo público') }}</h1>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ __('Grupos, subgrupos, departamentos (regional / local) e cargos para segmentação de eventos.') }}</p>
        </div>
    </x-slot>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <x-ui.card title="{{ __('Grupos') }}">
            <p class="text-sm text-slate-600 dark:text-slate-300">{{ __('Temas de público por regional.') }}</p>
            <a href="{{ route('agenda.public-groups.index') }}" wire:navigate class="mt-3 inline-block text-sm font-semibold text-primary-600 hover:underline">{{ __('Abrir') }} →</a>
        </x-ui.card>
        <x-ui.card title="{{ __('Departamentos') }}">
            <p class="text-sm text-slate-600 dark:text-slate-300">{{ __('Unidades funcionais: regional, administração ou casa.') }}</p>
            <a href="{{ route('agenda.public-departments.index') }}" wire:navigate class="mt-3 inline-block text-sm font-semibold text-primary-600 hover:underline">{{ __('Abrir') }} →</a>
        </x-ui.card>
        <x-ui.card title="{{ __('Cargos') }}">
            <p class="text-sm text-slate-600 dark:text-slate-300">{{ __('Funções ligadas a departamentos e opcionalmente a subgrupos.') }}</p>
            <a href="{{ route('agenda.public-positions.index') }}" wire:navigate class="mt-3 inline-block text-sm font-semibold text-primary-600 hover:underline">{{ __('Abrir') }} →</a>
        </x-ui.card>
    </div>
    <div class="mt-8 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600 dark:border-slate-800 dark:bg-slate-900/40 dark:text-slate-300">
        <p class="font-medium text-slate-800 dark:text-slate-100">{{ __('Públicos legados (lista plana)') }}</p>
        <p class="mt-1">{{ __('A lista antiga de audiências mantém-se para referência; o evento usa o catálogo hierárquico.') }}</p>
        <a href="{{ route('agenda.audiences.index') }}" wire:navigate class="mt-2 inline-block text-primary-600 hover:underline">{{ __('Audiências antigas') }}</a>
    </div>
</x-app-layout>
