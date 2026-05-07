<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">{{ isset($subgroup) ? __('Editar subgrupo') : __('Novo subgrupo') }}</h1>
    </x-slot>
    @isset($subgroup)
        <livewire:agenda.public-subgroup-form :group="$group" :public-subgroup="$subgroup" wire:key="psgf-{{ $subgroup->id }}" />
    @else
        <livewire:agenda.public-subgroup-form :group="$group" wire:key="psgf-new-{{ $group->id }}" />
    @endisset
</x-app-layout>
