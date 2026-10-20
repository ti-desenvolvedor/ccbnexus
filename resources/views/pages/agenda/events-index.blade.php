<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">{{ __('Eventos') }}</h1>
        </div>
        @can('create', \App\Models\Event::class)
            <a href="{{ route('agenda.events.create') }}" wire:navigate class="w-full sm:w-auto"><x-ui.button class="min-h-11 w-full sm:w-auto">{{ __('Novo evento') }}</x-ui.button></a>
        @endcan
        </div>
    </x-slot>
    <livewire:agenda.event-list />
</x-app-layout>
