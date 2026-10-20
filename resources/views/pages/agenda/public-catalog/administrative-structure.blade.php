<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <nav class="text-xs text-slate-500 dark:text-slate-400">
                    <a href="{{ route('agenda.public-catalog.index') }}" wire:navigate class="hover:text-primary-600">{{ __('Agenda') }}</a>
                    <span class="mx-1">/</span>
                    <a href="{{ route('agenda.public-catalog.index') }}" wire:navigate class="hover:text-primary-600">{{ __('Catálogo público') }}</a>
                    <span class="mx-1">/</span>
                    <span class="text-slate-700 dark:text-slate-300">{{ __('Estrutura administrativa') }}</span>
                </nav>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">{{ __('Estrutura administrativa') }}</h1>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ __('Grupos I–V e 17 departamentos por âmbito regional e de administração.') }}</p>
            </div>
            @can('create', \App\Models\PublicDepartment::class)
                <a href="{{ route('agenda.public-departments.create') }}" wire:navigate>
                    <x-ui.button>{{ __('Adicionar departamento') }}</x-ui.button>
                </a>
            @endcan
        </div>
    </x-slot>
    <livewire:agenda.administrative-structure-explorer />
</x-app-layout>
