<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-primary-700 dark:text-primary-300">Operação</div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">Salas de reunião</h1>
            </div>
            @can('create', \App\Models\MeetingRoom::class)
                <a href="{{ route('infrastructure.meeting-rooms.create') }}" wire:navigate>
                    <x-ui.button>Nova sala</x-ui.button>
                </a>
            @endcan
        </div>
    </x-slot>
    <livewire:infrastructure.meeting-room-list />
</x-app-layout>
