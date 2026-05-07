<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="text-xs font-semibold uppercase tracking-wide text-primary-700 dark:text-primary-300">Operação</div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">{{ $meetingRoom ? 'Editar sala' : 'Nova sala' }}</h1>
        </div>
    </x-slot>
    <livewire:infrastructure.meeting-room-form :meeting-room="$meetingRoom" />
    @if ($meetingRoom)
        <div class="mt-8">
            <livewire:infrastructure.meeting-room-assignments :meeting-room="$meetingRoom" />
        </div>
    @endif
</x-app-layout>
