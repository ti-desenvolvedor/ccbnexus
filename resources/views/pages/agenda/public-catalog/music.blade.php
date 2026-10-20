<x-app-layout>
    <x-slot name="header">
        <div>
            <nav class="text-xs text-slate-500 dark:text-slate-400">
                <a href="{{ route('agenda.public-catalog.index') }}" wire:navigate class="hover:text-primary-600">{{ __('Agenda') }}</a>
                <span class="mx-1">/</span>
                <a href="{{ route('agenda.public-catalog.index') }}" wire:navigate class="hover:text-primary-600">{{ __('Catálogo público') }}</a>
                <span class="mx-1">/</span>
                <span class="text-slate-700 dark:text-slate-300">{{ __('Música') }}</span>
            </nav>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">{{ __('Música') }}</h1>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ __('Carteiras regionais, encarregados por administração e cargos na casa de oração.') }}</p>
        </div>
    </x-slot>
    <livewire:agenda.catalog-axis-explorer axis="music" />
</x-app-layout>
