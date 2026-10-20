<div class="space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0 flex-1">
            <label class="block text-xs font-semibold text-slate-500">{{ __('Sala') }}</label>
            <x-nexus.searchable-select
                wire:model.live="meeting_room_id"
                class="mt-1"
                :placeholder="__('Nenhuma sala disponível')"
                :search-placeholder="__('Buscar sala...')"
                :options="$rooms"
            />
        </div>
        <div class="flex items-center gap-2">
            <x-ui.button type="button" variant="secondary" size="sm" wire:click="previousWeek">←</x-ui.button>
            <div class="min-w-[9rem] text-center text-sm font-semibold">{{ $weekLabel }}</div>
            <x-ui.button type="button" variant="secondary" size="sm" wire:click="nextWeek">→</x-ui.button>
        </div>
    </div>

    @if ($rooms->isEmpty())
        <x-nexus.empty-state
            :title="__('Sem salas')"
            :description="__('Crie uma sala em Operação → Salas para usar o calendário.')"
            :href="route('infrastructure.meeting-rooms.create')"
            :action-label="__('Criar sala')"
        />
    @else
        <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
            <table class="min-w-full border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-900">
                        <th class="sticky left-0 z-10 bg-slate-50 px-2 py-2 text-left dark:bg-slate-900">{{ __('Hora') }}</th>
                        @foreach ($days as $day)
                            <th class="px-2 py-2 text-center font-semibold">{{ $day->translatedFormat('D d/m') }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($hours as $hour)
                        <tr class="border-t border-slate-100 dark:border-slate-800">
                            <td class="sticky left-0 z-10 bg-white px-2 py-1 font-medium dark:bg-slate-950">{{ sprintf('%02d:00', $hour) }}</td>
                            @foreach ($days as $day)
                                @php
                                    $key = $day->format('Y-m-d').'|'.$hour;
                                    $status = $occupied[$key] ?? null;
                                @endphp
                                <td class="px-1 py-1 text-center">
                                    @if ($status)
                                        <span @class([
                                            'inline-flex min-h-[36px] w-full items-center justify-center rounded-lg px-1 font-semibold',
                                            'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200' => $status === 'approved',
                                            'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200' => $status === 'pending',
                                        ])>
                                            {{ $status === 'approved' ? __('Ocupado') : __('Pendente') }}
                                        </span>
                                    @else
                                        <button
                                            type="button"
                                            wire:click="bookSlot('{{ $day->toDateString() }}', {{ $hour }})"
                                            class="inline-flex min-h-[36px] w-full items-center justify-center rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-200"
                                            title="{{ __('Reservar') }}"
                                        >
                                            {{ __('Livre') }}
                                        </button>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="space-y-2">
            <div class="text-sm font-semibold">{{ __('Reservas da semana') }}</div>
            @forelse ($reservations as $r)
                <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800">
                    <div>
                        <div class="font-medium">{{ $r->title }}</div>
                        <div class="text-xs text-slate-500">{{ $r->starts_at->format('d/m H:i') }} – {{ $r->ends_at->format('H:i') }}</div>
                    </div>
                    <x-ui.badge>{{ $r->status }}</x-ui.badge>
                </div>
            @empty
                <p class="text-xs text-slate-500">{{ __('Nenhuma reserva nesta semana.') }}</p>
            @endforelse
        </div>
    @endif
</div>
