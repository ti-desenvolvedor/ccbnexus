<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">{{ $publicGroup ? __('Editar grupo') : __('Novo grupo') }}</h1>
    </x-slot>
    <livewire:agenda.public-group-form :public-group="$publicGroup" wire:key="pgf-{{ $publicGroup?->id ?? 'new' }}" />
</x-app-layout>
