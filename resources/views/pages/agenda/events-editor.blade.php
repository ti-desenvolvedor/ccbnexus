<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="text-xs font-semibold uppercase tracking-wide text-primary-700 dark:text-primary-300">Agenda</div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">{{ $event ? 'Editar evento' : 'Novo evento' }}</h1>
        </div>
    </x-slot>
    @if ($event)
        @can('respond', $event)
            <p class="mb-4 text-sm">
                <a href="{{ route('agenda.events.rsvp', $event) }}" wire:navigate class="font-semibold text-primary-600 hover:underline">Confirmar a minha participação / refeições</a>
            </p>
        @endcan
        <livewire:agenda.event-form :event="$event" wire:key="ev-{{ $event->id }}" />
    @else
        <livewire:agenda.event-form wire:key="ev-new" />
    @endif
</x-app-layout>
