<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-primary-700 dark:text-primary-300">Organização</div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">Casas de oração</h1>
            </div>
            @can('create', \App\Models\PrayerHouse::class)
                <a href="{{ route('organization.prayer-houses.create') }}" wire:navigate>
                    <x-ui.button>Nova casa</x-ui.button>
                </a>
            @endcan
        </div>
    </x-slot>
    <livewire:organization.prayer-house-list />
</x-app-layout>
