<div class="space-y-4">
    <div class="flex flex-wrap items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
        <span>{{ __('Contexto:') }}</span>
        <span class="font-semibold text-slate-900 dark:text-slate-100">{{ $activeRegional?->name ?? __('todas as regionais do seu acesso') }}</span>
        @if ($result->includesDrafts)
            <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">Inclui rascunhos</span>
        @endif
    </div>

    @if (! $activeRegional)
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-100">
            Selecione uma regional no topo para gerar o PDF. Esta tela mostra todas as regionais a que tem acesso.
        </div>
    @endif

    @if ($periodInvalid)
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-100">
            Período inválido.
        </div>
    @endif

    @if ($holidayNotice !== '')
        <div class="rounded-xl border px-4 py-3 text-sm {{ $holidayNoticeIsWarning ? 'border-amber-200 bg-amber-50 text-amber-950 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-100' : 'border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-100' }}" role="status">
            {{ $holidayNotice }}
        </div>
    @endif

    <div class="flex flex-col gap-3">
        <div role="tablist" aria-label="Vistas da agenda" class="grid grid-cols-2 gap-2 sm:grid-cols-4">
            @foreach (['week' => 'Calendário', 'list' => 'Lista', 'month' => 'Mensal', 'agenda' => 'Agenda'] as $key => $label)
                <button
                    type="button"
                    role="tab"
                    wire:click="setView('{{ $key }}')"
                    aria-selected="{{ $view === $key ? 'true' : 'false' }}"
                    class="inline-flex min-h-11 items-center justify-center rounded-xl px-3 text-sm font-semibold focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-950 {{ $view === $key ? 'bg-primary-600 text-white' : 'border border-slate-200 bg-white text-slate-800 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100' }}"
                >{{ $label }}</button>
            @endforeach
        </div>
        <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:justify-end">
            @if (in_array($view, ['month', 'agenda'], true))
                @if ($pdfUrl)
                    <x-ui.button variant="secondary" :href="$pdfUrl" target="_blank" rel="noopener" class="min-h-11 w-full sm:w-auto" title="Agenda de janeiro a dezembro de {{ $pdfYear }}">Gerar PDF</x-ui.button>
                @else
                    <button type="button" disabled aria-disabled="true" title="Agenda de janeiro a dezembro de {{ $pdfYear }}" class="inline-flex min-h-11 w-full cursor-not-allowed items-center justify-center rounded-xl border border-slate-200 px-4 text-sm font-semibold text-slate-500 sm:w-auto dark:border-slate-800">Gerar PDF</button>
                @endif
            @endif
            @if ($canCreate)
                @if ($activeRegional)
                    <button type="button" wire:click="importHolidays" wire:loading.attr="disabled" wire:target="importHolidays" title="Atualiza o ano atual e o seguinte: feriados e recorrências que fecham em 31 de dezembro. Não duplica e os feriados não entram na lista." class="inline-flex min-h-11 w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-900 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 sm:w-auto dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100 dark:hover:bg-slate-900">Sincronizar calendário</button>
                @else
                    <button type="button" disabled aria-disabled="true" title="Selecione uma regional no topo para sincronizar o calendário da cidade." class="inline-flex min-h-11 w-full cursor-not-allowed items-center justify-center rounded-xl border border-slate-200 px-4 text-sm font-semibold text-slate-500 sm:w-auto dark:border-slate-800">Sincronizar calendário</button>
                @endif
                @if ($holidaySync)
                    <p class="w-full text-xs text-slate-600 sm:text-right dark:text-slate-300">Última sincronização: {{ $holidaySync->ran_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}. Ano atual e o seguinte, uma vez por mês.</p>
                @endif
            @endif
        </div>
    </div>

    @if ($view === 'list')
        <div class="flex gap-2" role="group" aria-label="Modo da lista">
            <button type="button" wire:click="setListMode('occurrences')" class="inline-flex min-h-11 flex-1 items-center justify-center rounded-xl px-4 text-sm font-semibold focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 sm:flex-none {{ $listMode === 'occurrences' ? 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900' : 'border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950' }}">Ocorrências</button>
            <button type="button" wire:click="setListMode('series')" class="inline-flex min-h-11 flex-1 items-center justify-center rounded-xl px-4 text-sm font-semibold focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 sm:flex-none {{ $listMode === 'series' ? 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900' : 'border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950' }}">Séries</button>
        </div>
    @endif

    @if ($showPeriod)
        <div class="space-y-2">
            <p class="text-center text-base font-semibold capitalize text-slate-900 dark:text-slate-100">{{ $periodLabel }}</p>
            <div class="grid grid-cols-3 gap-2">
                <button type="button" wire:click="previousPeriod" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-2 text-sm font-semibold text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100">Anterior</button>
                <button type="button" wire:click="goToday" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-2 text-sm font-semibold text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100">Hoje</button>
                <button type="button" wire:click="nextPeriod" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-2 text-sm font-semibold text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100">Seguinte</button>
            </div>
        </div>
    @endif

    <div class="flex flex-col gap-3 lg:flex-row lg:flex-wrap lg:items-end">
        <div class="w-full max-w-md">
            <label for="event-search" class="text-xs font-semibold text-slate-700 dark:text-slate-200">Buscar eventos</label>
            <input id="event-search" type="search" wire:model.live.debounce.300ms="search" class="mt-1 min-h-[44px] w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-base dark:border-slate-800 dark:bg-slate-950" />
        </div>
        <div class="w-full sm:w-64">
            <label class="text-xs font-semibold text-slate-700 dark:text-slate-200">Tipo</label>
            <div class="mt-1">
                <x-nexus.searchable-select
                    wire:model.live="typeFilter"
                    :options="collect([['value' => '0', 'label' => 'Todos']])->merge($eventTypes->map(fn ($t) => ['value' => (string) $t->id, 'label' => $t->name]))"
                    placeholder="Todos"
                    searchPlaceholder="Buscar tipo..."
                />
            </div>
        </div>
        <div>
            <label for="event-status-filter" class="text-xs font-semibold text-slate-700 dark:text-slate-200">Situação</label>
            <x-nexus.searchable-select
                id="event-status-filter"
                wire:model.live="statusFilter"
                class="mt-1 min-w-[12rem]"
                placeholder="Todas"
                search-placeholder="Buscar situação..."
                :options="[
                    ['value' => '', 'label' => 'Todas'],
                    ['value' => 'draft', 'label' => 'Rascunho'],
                    ['value' => 'pending_approval', 'label' => 'Pendente'],
                    ['value' => 'published', 'label' => 'Publicado'],
                ]"
            />
        </div>
        <div>
            <label for="event-channel-filter" class="text-xs font-semibold text-slate-700 dark:text-slate-200">Canal</label>
            <x-nexus.searchable-select
                id="event-channel-filter"
                wire:model.live="channelFilter"
                class="mt-1 min-w-[12rem]"
                placeholder="Todos"
                search-placeholder="Buscar canal..."
                :options="[
                    ['value' => '', 'label' => 'Todos'],
                    ['value' => 'bulletin', 'label' => 'Lista de batismos'],
                    ['value' => 'whatsapp', 'label' => 'WhatsApp'],
                    ['value' => 'bulletin_only', 'label' => 'Só boletim'],
                    ['value' => 'whatsapp_only', 'label' => 'Só WhatsApp'],
                ]"
            />
        </div>
    </div>

    @if ($result->truncated)
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-100">
            Primeiras 500 ocorrências. O período está incompleto.
        </div>
    @endif

    @if ($pendingEvents !== [])
        <section aria-labelledby="pending-events-heading" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-900 dark:bg-amber-950/40">
            <h2 id="pending-events-heading" class="text-sm font-semibold text-amber-950 dark:text-amber-100">Para aprovar</h2>
            <p class="mt-1 text-sm text-amber-950 dark:text-amber-100">Listagem do que está pendente nesta vista. A aprovação continua na fila.</p>
            <ul class="mt-3 divide-y divide-amber-200 dark:divide-amber-900">
                @foreach ($pendingEvents as $pending)
                    <li class="flex flex-col gap-1 py-2 sm:flex-row sm:items-center sm:justify-between">
                        <span class="text-sm font-medium text-amber-950 dark:text-amber-100">{{ $pending['when'] }} · {{ $pending['title'] }}</span>
                        @if ($pending['canUpdate'])
                            <a href="{{ route('agenda.events.edit', $pending['editId']) }}" wire:navigate class="inline-flex min-h-11 items-center text-sm font-semibold text-amber-950 underline dark:text-amber-100">Abrir</a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <div wire:loading.class="opacity-50" class="space-y-4">
        @if ($view === 'week')
            @include('livewire.agenda.partials.event-week')
        @elseif ($view === 'month')
            @include('livewire.agenda.partials.event-month')
        @elseif ($view === 'agenda')
            @include('livewire.agenda.partials.event-agenda')
        @elseif ($listMode === 'series')
            @include('livewire.agenda.partials.event-series')
        @else
            @include('livewire.agenda.partials.event-occurrences')
        @endif
    </div>
</div>
