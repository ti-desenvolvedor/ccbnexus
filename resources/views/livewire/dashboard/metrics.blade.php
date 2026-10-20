<div class="space-y-8" wire:init="refresh">
    <div wire:loading.flex wire:target="refresh" class="flex-col gap-3" aria-hidden="true">
        <x-ui.skeleton class="h-24 w-full" />
        <x-ui.skeleton class="h-64 w-full" />
    </div>

    <div wire:loading.remove wire:target="refresh" class="space-y-8">
        @if ($ready)
            <p class="text-sm text-slate-600 dark:text-slate-300">
                {{ $panel['scopeLabel'] }}
                <span aria-hidden="true"> · </span>
                {{ $panel['weekLabel'] }}
            </p>

            <section aria-labelledby="attention-heading">
                <h2 id="attention-heading" class="text-lg font-semibold text-slate-900 dark:text-slate-100">O que pede decisão</h2>
                @if ($panel['attention'] === [])
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Nada pendente neste recorte.</p>
                @else
                    <ul class="mt-3 flex gap-2 overflow-x-auto pb-1 nexus-scroll">
                        @foreach ($panel['attention'] as $item)
                            <li wire:key="attention-{{ $item['href'] }}" class="shrink-0">
                                <a
                                    href="{{ $item['href'] }}"
                                    wire:navigate
                                    class="inline-flex min-h-11 items-center gap-2 rounded-full px-3 text-sm font-semibold focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 {{ $item['tone'] === 'danger' ? 'bg-rose-100 text-rose-950 dark:bg-rose-950 dark:text-rose-100' : 'bg-amber-100 text-amber-950 dark:bg-amber-950 dark:text-amber-100' }}"
                                >
                                    <span class="tabular-nums">{{ $item['value'] }}</span>
                                    {{ $item['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            @if ($panel['stats'] !== [])
                <section aria-labelledby="numbers-heading">
                    <h2 id="numbers-heading" class="text-lg font-semibold text-slate-900 dark:text-slate-100">Números do recorte</h2>
                    <ul class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                        @foreach ($panel['stats'] as $stat)
                            <li wire:key="stat-{{ $stat['label'] }}">
                                @if ($stat['href'])
                                    <a href="{{ $stat['href'] }}" wire:navigate class="flex h-full flex-col rounded-2xl border border-slate-200/80 bg-white px-4 py-3 shadow-sm shadow-slate-900/5 hover:border-primary-300 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 dark:border-slate-800 dark:bg-slate-950 dark:shadow-black/20 dark:hover:border-primary-700">
                                        @include('livewire.dashboard.partials.stat', ['stat' => $stat])
                                    </a>
                                @else
                                    <div class="flex h-full flex-col rounded-2xl border border-slate-200/80 bg-white px-4 py-3 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-950 dark:shadow-black/20">
                                        @include('livewire.dashboard.partials.stat', ['stat' => $stat])
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <div class="grid gap-8 lg:grid-cols-2">
                <section aria-labelledby="week-heading" class="min-w-0">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <h2 id="week-heading" class="text-lg font-semibold text-slate-900 dark:text-slate-100">Esta semana</h2>
                        <p class="text-sm text-slate-600 dark:text-slate-300">{{ $panel['weekLabel'] }}</p>
                    </div>
                    @if ($panel['canSeeEvents'])
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                            @if ($panel['confirmedPresences'] === 1)
                                1 presença confirmada.
                            @else
                                {{ $panel['confirmedPresences'] }} presenças confirmadas.
                            @endif
                        </p>
                    @endif
                    <div class="nexus-scroll mt-3 max-h-96 overflow-y-auto rounded-2xl border border-slate-200/80 bg-white px-4 dark:border-slate-800 dark:bg-slate-950">
                        @if ($panel['weekEvents'] === [])
                            <div class="py-6">
                                <p class="text-base font-semibold text-slate-900 dark:text-slate-100">
                                    Nenhum culto nesta semana{{ $panel['regionalName'] ? ' em '.$panel['regionalName'] : '' }}.
                                </p>
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">A agenda da regional aparece aqui quando houver data dentro destes sete dias.</p>
                                @if ($panel['canCreateEvent'])
                                    <x-ui.button class="mt-4" href="{{ route('agenda.events.create') }}">Novo evento</x-ui.button>
                                @endif
                            </div>
                        @else
                            <ul class="divide-y divide-slate-200 dark:divide-slate-800">
                                @foreach ($panel['weekEvents'] as $event)
                                    @include('livewire.dashboard.partials.event-row', ['event' => $event])
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </section>

                <section aria-labelledby="upcoming-heading" class="min-w-0">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <h2 id="upcoming-heading" class="text-lg font-semibold text-slate-900 dark:text-slate-100">Próximos eventos</h2>
                        <p class="text-sm text-slate-600 dark:text-slate-300">21 dias depois desta semana</p>
                    </div>
                    <div class="nexus-scroll mt-3 max-h-96 overflow-y-auto rounded-2xl border border-slate-200/80 bg-white px-4 dark:border-slate-800 dark:bg-slate-950">
                        @if ($panel['upcomingEvents'] === [])
                            <div class="py-6">
                                <p class="text-base font-semibold text-slate-900 dark:text-slate-100">Nada marcado depois desta semana.</p>
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Os eventos das três semanas seguintes aparecem aqui.</p>
                            </div>
                        @else
                            <ul class="divide-y divide-slate-200 dark:divide-slate-800">
                                @foreach ($panel['upcomingEvents'] as $event)
                                    @include('livewire.dashboard.partials.event-row', ['event' => $event])
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </section>
            </div>

            @if ($panel['canSeeEvents'] || $panel['canSeeMembers'])
                <div class="grid gap-8 lg:grid-cols-2">
                    @if ($panel['canSeeEvents'])
                        <section aria-labelledby="rhythm-heading" class="rounded-2xl border border-slate-200/80 bg-white p-4 dark:border-slate-800 dark:bg-slate-950">
                            <h2 id="rhythm-heading" class="text-lg font-semibold text-slate-900 dark:text-slate-100">Ritmo da agenda</h2>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Eventos por semana, oito semanas a partir deste domingo. Cancelados ficam de fora.</p>
                            @include('livewire.dashboard.partials.week-chart', ['weeks' => $panel['weekChart'], 'status' => $panel['eventStatus']])
                        </section>
                    @endif
                    @if ($panel['canSeeMembers'])
                        <section aria-labelledby="ministry-chart-heading" class="rounded-2xl border border-slate-200/80 bg-white p-4 dark:border-slate-800 dark:bg-slate-950">
                            <h2 id="ministry-chart-heading" class="text-lg font-semibold text-slate-900 dark:text-slate-100">Cadastros por ministério</h2>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Pessoas em cada função do ministério.</p>
                            @include('livewire.dashboard.partials.ministry-chart', ['rows' => $panel['ministryChart'], 'remainder' => $panel['ministryRemainder']])
                        </section>
                    @endif
                </div>
            @endif

            @if ($panel['canSeeLists'])
                <section aria-labelledby="lists-heading">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <h2 id="lists-heading" class="text-lg font-semibold text-slate-900 dark:text-slate-100">Listas de batismos</h2>
                        <a href="{{ route('secretaria.baptism-list') }}" wire:navigate class="text-sm font-semibold text-primary-700 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 dark:text-primary-300">Abrir a lista</a>
                    </div>
                    <div class="mt-3 grid gap-6 lg:grid-cols-2">
                        @include('livewire.dashboard.partials.report-list', [
                            'title' => 'Últimas geradas',
                            'empty' => 'Nenhuma lista gerada neste recorte.',
                            'reports' => $panel['generatedLists'],
                        ])
                        @include('livewire.dashboard.partials.report-list', [
                            'title' => 'Últimas confirmadas',
                            'empty' => 'Nenhuma lista confirmada. A versão validada aparece aqui.',
                            'reports' => $panel['confirmedLists'],
                        ])
                    </div>
                </section>
            @endif

            @if ($panel['canSeeReservations'])
                <section aria-labelledby="rooms-heading">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <h2 id="rooms-heading" class="text-lg font-semibold text-slate-900 dark:text-slate-100">Reservas desta semana</h2>
                        <a href="{{ route('infrastructure.room-reservations.index') }}" wire:navigate class="text-sm font-semibold text-primary-700 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 dark:text-primary-300">Abrir reservas</a>
                    </div>
                    <div class="nexus-scroll mt-3 max-h-72 overflow-y-auto rounded-2xl border border-slate-200/80 bg-white px-4 dark:border-slate-800 dark:bg-slate-950">
                        @if ($panel['reservations'] === [])
                            <p class="py-6 text-sm text-slate-600 dark:text-slate-300">Nenhuma reserva ativa nestes sete dias.</p>
                        @else
                            <ul class="divide-y divide-slate-200 dark:divide-slate-800">
                                @foreach ($panel['reservations'] as $reservation)
                                    <li wire:key="room-{{ $reservation['id'] }}" class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="min-w-0">
                                            <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $reservation['title'] }}</p>
                                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                                                {{ $reservation['when'] }}
                                                @if ($reservation['place'])
                                                    · {{ $reservation['place'] }}
                                                @endif
                                            </p>
                                        </div>
                                        <x-ui.badge :tone="$reservation['tone']">{{ $reservation['status'] }}</x-ui.badge>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </section>
            @endif

            @if ($panel['secretariaLinks'] !== [])
                <section aria-labelledby="secretaria-heading">
                    <h2 id="secretaria-heading" class="text-lg font-semibold text-slate-900 dark:text-slate-100">Secretaria</h2>
                    <ul class="mt-3 flex flex-wrap gap-2">
                        @foreach ($panel['secretariaLinks'] as $link)
                            <li>
                                <a href="{{ $link['href'] }}" wire:navigate class="inline-flex min-h-11 items-center rounded-full border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-800 hover:border-primary-300 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100 dark:hover:border-primary-700">
                                    {{ $link['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        @endif
    </div>
</div>
