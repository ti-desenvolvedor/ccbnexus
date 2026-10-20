@if ($result->entries()->isEmpty() && ! $periodHasHoliday)
    <x-nexus.empty-state
        title="Nenhum evento neste período"
        description="Ajuste a pesquisa, o tipo ou o canal, ou mude o mês."
        :href="$canCreate ? route('agenda.events.create') : null"
        action-label="Novo evento"
    />
@else
<div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
    <table class="min-w-[720px] border-collapse text-xs">
        <caption class="sr-only">Calendário semanal de eventos</caption>
        <thead>
            <tr class="bg-slate-50 dark:bg-slate-900">
                <th class="sticky left-0 z-10 bg-slate-50 px-2 py-2 text-left dark:bg-slate-900">Hora</th>
                @foreach ($weekDays as $day)
                    <th class="px-2 py-2 text-center font-semibold {{ $day['isToday'] ? 'text-primary-700 dark:text-primary-300' : '' }}">
                        <div>{{ $day['label'] }}</div>
                        @foreach ($holidaysByDate[$day['date']] ?? [] as $holidayName)
                            <div class="mt-1 truncate text-[11px] font-semibold text-amber-800 dark:text-amber-200" title="{{ $holidayName }}">{{ $holidayName }}</div>
                        @endforeach
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($weekHours as $hour)
                <tr class="border-t border-slate-100 dark:border-slate-800">
                    <td class="sticky left-0 z-10 bg-white px-2 py-2 font-medium dark:bg-slate-950">{{ sprintf('%02d:00', $hour) }}</td>
                    @foreach ($weekDays as $day)
                        @php
                            $cell = $weekBlocks[$day['date'].'|'.$hour] ?? ['starts' => [], 'continues' => false];
                        @endphp
                        <td class="min-h-[44px] px-1 py-1 align-top {{ $cell['continues'] ? 'bg-emerald-50/80 dark:bg-emerald-950/30' : '' }}">
                            @foreach ($cell['starts'] as $block)
                                @php
                                    $tone = match ($block['status']) {
                                        'Pendente' => 'bg-amber-100 text-amber-950 dark:bg-amber-950 dark:text-amber-100',
                                        'Rascunho' => 'bg-slate-200 text-slate-800 dark:bg-slate-800 dark:text-slate-100',
                                        default => 'bg-emerald-100 text-emerald-950 dark:bg-emerald-950 dark:text-emerald-100',
                                    };
                                @endphp
                                @if (! empty($canUpdate[$block['editId']]))
                                    <a href="{{ route('agenda.events.edit', $block['editId']) }}" wire:navigate class="mb-1 block truncate rounded-lg px-1 py-1 font-semibold {{ $tone }}">{{ $block['time'] }} {{ $block['title'] }}@if ($block['status']) · {{ $block['status'] }} @endif</a>
                                @else
                                    <div class="mb-1 truncate rounded-lg px-1 py-1 font-semibold {{ $tone }}">{{ $block['time'] }} {{ $block['title'] }}@if ($block['status']) · {{ $block['status'] }} @endif</div>
                                @endif
                            @endforeach
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
