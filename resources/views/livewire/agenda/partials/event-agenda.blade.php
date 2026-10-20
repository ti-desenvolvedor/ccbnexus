@if ($agendaDays === [])
    <x-nexus.empty-state
        title="Nenhum evento neste período"
        description="Ajuste a pesquisa, o tipo ou o canal, ou mude o mês."
        :href="$canCreate ? route('agenda.events.create') : null"
        action-label="Novo evento"
    />
@else
    <div class="space-y-6">
        @foreach ($agendaDays as $day)
            <section>
                <h2 class="sticky top-0 z-10 bg-white/95 py-2 text-sm font-semibold capitalize text-slate-900 dark:bg-slate-950/95 dark:text-slate-100">{{ $day['heading'] }}</h2>
                @if (! empty($holidaysByDate[$day['date']] ?? []))
                    <p class="text-xs font-semibold text-amber-800 dark:text-amber-200">{{ implode(' · ', $holidaysByDate[$day['date']]) }}</p>
                @endif
                <div class="space-y-2">
                    @foreach ($day['events'] as $event)
                        <article class="rounded-xl border border-slate-200 px-3 py-3 text-sm dark:border-slate-800">
                            <div class="font-semibold">
                                @if (! empty($canUpdate[$event['editId']]))
                                    <a href="{{ route('agenda.events.edit', $event['editId']) }}" wire:navigate class="text-primary-700 hover:underline dark:text-primary-300">{{ $event['time'] }} {{ $event['title'] }}</a>
                                @else
                                    {{ $event['time'] }} {{ $event['title'] }}
                                @endif
                                @if ($event['status'])
                                    <x-ui.badge :tone="$event['status'] === 'Pendente' ? 'warning' : 'neutral'" class="ml-2">{{ $event['status'] }}</x-ui.badge>
                                @endif
                            </div>
                            <p class="mt-1 text-slate-600 dark:text-slate-300">
                                {{ $event['type'] }} · {{ $event['place'] }} · {{ $event['attendant'] }}
                                @if ($event['channels'] !== [])
                                    · {{ implode(', ', $event['channels']) }}
                                @endif
                            </p>
                        </article>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
@endif
