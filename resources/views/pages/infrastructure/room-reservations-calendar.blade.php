<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">{{ __('Calendário de salas') }}</h1>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ __('Disponibilidade semanal com click-to-book (Open Meeting).') }}</p>
        </div>
    </x-slot>

    <livewire:infrastructure.room-calendar />
</x-app-layout>
