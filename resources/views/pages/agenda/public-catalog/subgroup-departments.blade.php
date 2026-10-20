<x-app-layout>
    <x-slot name="header">
        <div>
            <nav class="text-xs text-slate-500 dark:text-slate-400">
                <a href="{{ route('agenda.public-catalog.index') }}" wire:navigate class="hover:text-primary-600">{{ __('Catálogo público') }}</a>
                <span class="mx-1">/</span>
                <a href="{{ route('agenda.public-groups.index') }}" wire:navigate class="hover:text-primary-600">{{ $group->name }}</a>
                <span class="mx-1">/</span>
                <span class="text-slate-700 dark:text-slate-300">{{ $subgroup->name }}</span>
            </nav>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">{{ __('Departamentos') }}</h1>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ $subgroup->name }} — {{ $group->name }}</p>
        </div>
    </x-slot>
    <livewire:agenda.public-subgroup-department-list :group="$group" :subgroup="$subgroup" />
</x-app-layout>
