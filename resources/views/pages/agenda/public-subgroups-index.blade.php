<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">{{ __('Subgrupos') }}</h1>
    </x-slot>
    <livewire:agenda.public-subgroup-list :group="$group" wire:key="psgl-{{ $group->id }}" />
</x-app-layout>
