<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-primary-700 dark:text-primary-300">Agenda</div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">Públicos</h1>
            </div>
            @can('create', \App\Models\Audience::class)
                <a href="{{ route('agenda.audiences.create') }}" wire:navigate><x-ui.button>Novo público</x-ui.button></a>
            @endcan
        </div>
    </x-slot>
    <livewire:agenda.audience-list />
</x-app-layout>
