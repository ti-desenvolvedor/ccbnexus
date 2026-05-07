<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="text-xs font-semibold uppercase tracking-wide text-primary-700 dark:text-primary-300">Agenda</div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">{{ $audience ? 'Editar público' : 'Novo público' }}</h1>
        </div>
    </x-slot>
    @if ($audience)
        <livewire:agenda.audience-form :audience="$audience" wire:key="au-{{ $audience->id }}" />
    @else
        <livewire:agenda.audience-form wire:key="au-new" />
    @endif
</x-app-layout>
