<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="text-xs font-semibold uppercase tracking-wide text-primary-700 dark:text-primary-300">Organização</div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">
                {{ $prayerHouse ? 'Editar casa de oração' : 'Nova casa de oração' }}
            </h1>
        </div>
    </x-slot>
    <livewire:organization.prayer-house-form :prayer-house="$prayerHouse" />
</x-app-layout>
