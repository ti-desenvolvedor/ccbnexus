@if ($result->entries()->isEmpty() && ! $periodHasHoliday)
    <x-nexus.empty-state
        title="Nenhum evento neste período"
        description="Ajuste a pesquisa, o tipo ou o canal, ou mude o mês."
        :href="$canCreate ? route('agenda.events.create') : null"
        action-label="Novo evento"
    />
@else
    <div class="w-full max-w-full overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
        <table class="w-full table-fixed border-collapse text-xs" style="table-layout: fixed; width: 100%;">
            <caption class="sr-only">Grelha mensal de eventos</caption>
            <thead>
                <tr class="bg-slate-50 dark:bg-slate-900">
                    @foreach (['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'] as $name)
                        <th class="px-2 py-2 text-center font-semibold">{{ $name }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($weeks as $week)
                    <tr>
                        @foreach ($week as $day)
                            <td class="h-24 overflow-hidden border-t border-slate-100 px-1 py-1 align-top dark:border-slate-800 {{ $day['inMonth'] ? '' : 'bg-slate-50 text-slate-600 dark:bg-slate-900/40 dark:text-slate-300' }} {{ $day['isToday'] ? 'ring-2 ring-inset ring-primary-500' : '' }}">
                                <div class="font-semibold">{{ $day['day'] }}</div>
                                @if ($day['inMonth'] && ! empty($holidaysByDate[$day['date']] ?? []))
                                    <div class="truncate text-[11px] font-semibold leading-4 text-amber-800 dark:text-amber-200" title="{{ implode(' · ', $holidaysByDate[$day['date']]) }}">{{ implode(' · ', $holidaysByDate[$day['date']]) }}</div>
                                @endif
                                @foreach ($day['events'] as $event)
                                    @php
                                        $pending = ($event['status'] ?? null) === 'Pendente';
                                        $chip = 'mt-1 block truncate '.($pending ? 'font-semibold text-amber-800 dark:text-amber-200' : 'text-primary-700 dark:text-primary-300');
                                    @endphp
                                    @if (! empty($canUpdate[$event['editId']]))
                                        <a href="{{ route('agenda.events.edit', $event['editId']) }}" wire:navigate class="{{ $chip }} hover:underline">{{ $event['time'] }} {{ $event['title'] }}@if ($pending) · Pendente @endif</a>
                                    @else
                                        <div class="{{ $chip }}">{{ $event['time'] }} {{ $event['title'] }}@if ($pending) · Pendente @endif</div>
                                    @endif
                                @endforeach
                                @if ($day['more'] > 0)
                                    <button type="button" wire:click="setView('agenda')" class="mt-1 flex w-full items-center justify-center rounded-md bg-primary-50 px-1 py-1 text-xs font-semibold text-primary-800 hover:bg-primary-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:bg-primary-950 dark:text-primary-100">+{{ $day['more'] }}</button>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
