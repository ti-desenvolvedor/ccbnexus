<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-primary-700 dark:text-primary-300">{{ __('Catálogo público') }}</div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">{{ __('Cargos') }}</h1>
            </div>
            @can('create', \App\Models\PublicPosition::class)
                <a href="{{ route('agenda.public-positions.create') }}" wire:navigate><x-ui.button>{{ __('Novo cargo') }}</x-ui.button></a>
            @endcan
        </div>
    </x-slot>
    <livewire:agenda.public-position-list />
</x-app-layout>
