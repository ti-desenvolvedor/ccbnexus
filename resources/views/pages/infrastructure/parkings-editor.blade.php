<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="text-xs font-semibold uppercase tracking-wide text-primary-700 dark:text-primary-300">Operação</div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">{{ $parking ? 'Editar estacionamento' : 'Novo estacionamento' }}</h1>
        </div>
    </x-slot>
    @if ($parking)
        <livewire:infrastructure.parking-form :parking="$parking" wire:key="pk-{{ $parking->id }}" />
    @else
        <livewire:infrastructure.parking-form wire:key="pk-new" />
    @endif
</x-app-layout>
