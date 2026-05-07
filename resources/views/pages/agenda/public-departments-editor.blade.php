<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">{{ $department ? __('Editar departamento') : __('Novo departamento') }}</h1>
    </x-slot>
    <livewire:agenda.public-department-form :department="$department" wire:key="pdf-{{ $department?->id ?? 'new' }}" />
</x-app-layout>
