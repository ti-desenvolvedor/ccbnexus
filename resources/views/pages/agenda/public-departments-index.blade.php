<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-primary-700 dark:text-primary-300">{{ __('Catálogo público') }}</div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">{{ __('Departamentos') }}</h1>
            </div>
            @can('create', \App\Models\PublicDepartment::class)
                <a href="{{ route('agenda.public-departments.create') }}" wire:navigate><x-ui.button>{{ __('Novo departamento') }}</x-ui.button></a>
            @endcan
        </div>
    </x-slot>
    <livewire:agenda.public-department-list />
</x-app-layout>
