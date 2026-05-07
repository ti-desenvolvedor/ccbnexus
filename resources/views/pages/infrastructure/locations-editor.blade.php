<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="text-xs font-semibold uppercase tracking-wide text-primary-700 dark:text-primary-300">Infraestrutura</div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">{{ $location ? 'Editar local' : 'Novo local' }}</h1>
        </div>
    </x-slot>
    <livewire:infrastructure.location-form :location="$location" />
</x-app-layout>
