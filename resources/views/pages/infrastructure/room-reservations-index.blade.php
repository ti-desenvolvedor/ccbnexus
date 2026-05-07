<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-primary-700 dark:text-primary-300">Operação</div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">Reservas de salas</h1>
            </div>
            @can('create', \App\Models\RoomReservation::class)
                <a href="{{ route('infrastructure.room-reservations.create') }}" wire:navigate>
                    <x-ui.button>Nova reserva</x-ui.button>
                </a>
            @endcan
        </div>
    </x-slot>
    <livewire:infrastructure.room-reservation-list />
</x-app-layout>
