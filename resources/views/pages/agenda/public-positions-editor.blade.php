<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">{{ $position ? __('Editar cargo') : __('Novo cargo') }}</h1>
    </x-slot>
    <livewire:agenda.public-position-form :position="$position" wire:key="ppf-{{ $position?->id ?? 'new' }}" />
</x-app-layout>
