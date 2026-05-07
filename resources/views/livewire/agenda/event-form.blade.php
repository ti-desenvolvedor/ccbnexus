<div class="space-y-6">
    <x-ui.card>
        <form wire:submit="save" class="grid w-full max-w-full gap-4">
            <div>
                <label class="text-xs font-semibold text-slate-500">Título do evento</label>
                <input type="text" wire:model="title" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
                @error('title') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex flex-wrap gap-2 border-b border-slate-200 pb-2 dark:border-slate-800">
                <button type="button" wire:click="$set('tab', 'main')" @class(['rounded-lg px-3 py-1.5 text-sm font-semibold', 'bg-primary-600 text-white' => $tab === 'main', 'text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-900' => $tab !== 'main'])>Dados</button>
                <button type="button" wire:click="$set('tab', 'local')" @class(['rounded-lg px-3 py-1.5 text-sm font-semibold', 'bg-primary-600 text-white' => $tab === 'local', 'text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-900' => $tab !== 'local'])>Local</button>
                <button type="button" wire:click="$set('tab', 'audience')" @class(['rounded-lg px-3 py-1.5 text-sm font-semibold', 'bg-primary-600 text-white' => $tab === 'audience', 'text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-900' => $tab !== 'audience'])>Público</button>
                <button type="button" wire:click="$set('tab', 'recurrence')" @class(['rounded-lg px-3 py-1.5 text-sm font-semibold', 'bg-primary-600 text-white' => $tab === 'recurrence', 'text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-900' => $tab !== 'recurrence'])>Recorrência</button>
                <button type="button" wire:click="$set('tab', 'structure')" @class(['rounded-lg px-3 py-1.5 text-sm font-semibold', 'bg-primary-600 text-white' => $tab === 'structure', 'text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-900' => $tab !== 'structure'])>Estrutura</button>
                <button type="button" wire:click="$set('tab', 'people')" @class(['rounded-lg px-3 py-1.5 text-sm font-semibold', 'bg-primary-600 text-white' => $tab === 'people', 'text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-900' => $tab !== 'people'])>Responsáveis</button>
            </div>

            @if ($tab === 'main')
                <div>
                    <label class="text-xs font-semibold text-slate-500">Regional</label>
                    <select wire:model="regional_id" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950">
                        <option value="">—</option>
                        @foreach ($regionals as $r)
                            <option value="{{ $r->id }}">{{ $r->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-500">Descrição</label>
                    <textarea wire:model="description" rows="4" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950"></textarea>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-500">Tipo</label>
                    <select wire:model="event_type_id" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950">
                        <option value="">—</option>
                        @foreach ($eventTypes as $t)
                            <option value="{{ $t->id }}">{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-xs font-semibold text-slate-500">Início</label>
                        <input type="datetime-local" wire:model="starts_at" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
                        @error('starts_at') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-500">Fim</label>
                        <input type="datetime-local" wire:model="ends_at" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
                        @error('ends_at') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-500">Estado</label>
                    <select wire:model="status" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950">
                        <option value="draft">Rascunho</option>
                        <option value="pending_approval">Pendente aprovação</option>
                        <option value="published">Publicado</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-500">Formato do evento</label>
                    <select wire:model.live="attendance_mode" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950">
                        <option value="in_person">Presencial</option>
                        <option value="online_only">Somente online</option>
                        <option value="hybrid">Híbrido (presencial e online)</option>
                    </select>
                    @error('attendance_mode') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                @if ($attendance_mode !== 'online_only')
                    <p class="text-xs text-slate-500 dark:text-slate-400">Local e sala definem-se na aba «Local».</p>
                @else
                    <p class="text-xs text-amber-700 dark:text-amber-300">Somente online: local e sala serão limpos ao guardar.</p>
                @endif
            @endif

            @if ($tab === 'local')
                @if ($attendance_mode === 'online_only')
                    <p class="text-sm text-slate-600 dark:text-slate-300">Este evento está como <strong>somente online</strong>. Não é necessário local físico.</p>
                @endif
                <div class="space-y-4 @if ($attendance_mode === 'online_only') pointer-events-none opacity-50 @endif">
                    <div>
                        <label class="text-xs font-semibold text-slate-500">Local</label>
                        <select wire:model="location_id" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950">
                            <option value="">—</option>
                            @foreach ($locations as $l)
                                <option value="{{ $l->id }}">{{ $l->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-500">Sala</label>
                        <select wire:model="meeting_room_id" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950">
                            <option value="">—</option>
                            @foreach ($meetingRooms as $m)
                                <option value="{{ $m->id }}">{{ $m->name }}</option>
                            @endforeach
                        </select>
                        @error('meeting_room_id') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            @endif

            @if ($tab === 'audience')
                <p class="text-sm text-slate-600 dark:text-slate-300">{{ __('Selecione os cargos / público-alvo (catálogo por regional).') }}</p>
                @if (! $regional_id)
                    <p class="text-sm text-amber-700 dark:text-amber-300">{{ __('Defina a regional em «Dados» para listar cargos.') }}</p>
                @elseif ($positionsGrouped->isEmpty())
                    <p class="text-sm text-slate-500">{{ __('Nenhum cargo ativo para esta regional. Configure em Catálogo público.') }}</p>
                @else
                    <div class="max-h-96 space-y-4 overflow-y-auto pr-1">
                        @foreach ($positionsGrouped as $groupLabel => $positions)
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $groupLabel }}</p>
                                <div class="mt-2 grid gap-2 sm:grid-cols-1">
                                    @foreach ($positions as $p)
                                        <label class="flex items-start gap-2 text-sm">
                                            <input type="checkbox" wire:model="public_position_ids" value="{{ $p->id }}" class="mt-1 rounded" />
                                            <span>{{ $p->labelForEventPicker() }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif

            @if ($tab === 'recurrence')
                @php
                    $recActive = in_array($recurrence_frequency, ['weekly', 'monthly_nth', 'yearly'], true);
                @endphp
                <div class="w-full max-w-full space-y-6">
                    <div class="grid min-w-0 gap-6 lg:grid-cols-2">
                        <div class="min-w-0 space-y-4 rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/60">
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Padrão de repetição</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Único, semanal, mensal por dia da semana no mês (ex.: 2.ª sexta) ou anual / bienal na mesma data de «Dados».</p>
                            <div class="flex w-full flex-row gap-1.5 rounded-xl bg-slate-100 p-1.5 dark:bg-slate-900" role="group" aria-label="{{ __('Padrão de repetição') }}">
                                <button
                                    type="button"
                                    wire:click="$set('recurrence_frequency', null)"
                                    aria-pressed="{{ ! $recurrence_frequency ? 'true' : 'false' }}"
                                    class="min-w-0 flex-1 rounded-lg px-2 py-2.5 text-center text-xs font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-950 sm:text-sm @if (! $recurrence_frequency) bg-white text-slate-900 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-800 dark:text-slate-100 dark:ring-slate-700 @else text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100 @endif"
                                ><span class="block truncate">Único</span></button>
                                <button
                                    type="button"
                                    wire:click="$set('recurrence_frequency', 'weekly')"
                                    aria-pressed="{{ $recurrence_frequency === 'weekly' ? 'true' : 'false' }}"
                                    class="min-w-0 flex-1 rounded-lg px-2 py-2.5 text-center text-xs font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-950 sm:text-sm @if ($recurrence_frequency === 'weekly') bg-white text-slate-900 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-800 dark:text-slate-100 dark:ring-slate-700 @else text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100 @endif"
                                ><span class="block truncate">Semanal</span></button>
                                <button
                                    type="button"
                                    wire:click="$set('recurrence_frequency', 'monthly_nth')"
                                    aria-pressed="{{ $recurrence_frequency === 'monthly_nth' ? 'true' : 'false' }}"
                                    class="min-w-0 flex-1 rounded-lg px-2 py-2.5 text-center text-xs font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-950 sm:text-sm @if ($recurrence_frequency === 'monthly_nth') bg-white text-slate-900 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-800 dark:text-slate-100 dark:ring-slate-700 @else text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100 @endif"
                                ><span class="block truncate">Mensal</span></button>
                                <button
                                    type="button"
                                    wire:click="$set('recurrence_frequency', 'yearly')"
                                    aria-pressed="{{ $recurrence_frequency === 'yearly' ? 'true' : 'false' }}"
                                    class="min-w-0 flex-1 rounded-lg px-2 py-2.5 text-center text-xs font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-950 sm:text-sm @if ($recurrence_frequency === 'yearly') bg-white text-slate-900 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-800 dark:text-slate-100 dark:ring-slate-700 @else text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100 @endif"
                                ><span class="block truncate">Anual</span></button>
                            </div>
                            @error('recurrence_frequency') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror

                            @if ($recurrence_frequency === 'weekly')
                                <div class="border-t border-slate-100 pt-4 dark:border-slate-800">
                                    <label class="text-xs font-semibold text-slate-500">Intervalo entre semanas</label>
                                    <select wire:model.live="recurrence_interval_weeks" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950">
                                        @for ($w = 1; $w <= 12; $w++)
                                            <option value="{{ $w }}">{{ $w === 1 ? 'Todas as semanas' : "A cada {$w} semanas" }}</option>
                                        @endfor
                                    </select>
                                    @error('recurrence_interval_weeks') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                                </div>
                            @endif

                            @if ($recurrence_frequency === 'yearly')
                                <div class="border-t border-slate-100 pt-4 dark:border-slate-800">
                                    <label class="text-xs font-semibold text-slate-500">Intervalo</label>
                                    <select wire:model.live="recurrence_interval_years" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950">
                                        @for ($y = 1; $y <= 20; $y++)
                                            <option value="{{ $y }}">{{ $y === 1 ? 'Todos os anos (anual)' : "A cada {$y} anos" }}</option>
                                        @endfor
                                    </select>
                                    @error('recurrence_interval_years') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">A data e a hora de cada ocorrência seguem o primeiro evento em «Dados» (dia/mês repetem; anos avançam conforme o intervalo).</p>
                                </div>
                            @endif

                            @if ($recurrence_frequency === 'monthly_nth')
                                @php
                                    $recurrenceMonthCells = [
                                        1 => ['Jan', 'Janeiro'],
                                        2 => ['Fev', 'Fevereiro'],
                                        3 => ['Mar', 'Março'],
                                        4 => ['Abr', 'Abril'],
                                        5 => ['Mai', 'Maio'],
                                        6 => ['Jun', 'Junho'],
                                        7 => ['Jul', 'Julho'],
                                        8 => ['Ago', 'Agosto'],
                                        9 => ['Set', 'Setembro'],
                                        10 => ['Out', 'Outubro'],
                                        11 => ['Nov', 'Novembro'],
                                        12 => ['Dez', 'Dezembro'],
                                    ];
                                @endphp
                                <div class="space-y-4 border-t border-slate-100 pt-4 dark:border-slate-800">
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <label class="text-xs font-semibold text-slate-500">Ocorrência no mês</label>
                                            <select wire:model.live="recurrence_monthly_nth" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950">
                                                <option value="1">1.ª</option>
                                                <option value="2">2.ª</option>
                                                <option value="3">3.ª</option>
                                                <option value="4">4.ª</option>
                                                <option value="5">5.ª</option>
                                                <option value="-1">Última do mês</option>
                                            </select>
                                            @error('recurrence_monthly_nth') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label class="text-xs font-semibold text-slate-500">Dia da semana</label>
                                            <select wire:model.live="recurrence_monthly_weekday" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950">
                                                <option value="0">Domingo</option>
                                                <option value="1">Segunda-feira</option>
                                                <option value="2">Terça-feira</option>
                                                <option value="3">Quarta-feira</option>
                                                <option value="4">Quinta-feira</option>
                                                <option value="5">Sexta-feira</option>
                                                <option value="6">Sábado</option>
                                            </select>
                                            @error('recurrence_monthly_weekday') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold text-slate-500">Meses em que aplica</p>
                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Um toque por opção. Em «Escolher meses» use a grelha abaixo (6 meses por linha).</p>
                                        <div class="mt-3 rounded-xl bg-slate-100 p-1.5 dark:bg-slate-900" role="group" aria-label="{{ __('Meses em que aplica') }}">
                                            <div class="flex w-full flex-row gap-1.5">
                                                <button
                                                    type="button"
                                                    wire:click="$set('recurrence_months_filter', 'all')"
                                                    aria-pressed="{{ $recurrence_months_filter === 'all' ? 'true' : 'false' }}"
                                                    class="flex min-h-[3.25rem] min-w-0 flex-1 flex-col items-center justify-center rounded-lg px-1.5 py-2 text-center transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-950 sm:min-h-[3.5rem] sm:px-2 sm:py-2.5 @if ($recurrence_months_filter === 'all') bg-white text-slate-900 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-800 dark:text-slate-100 dark:ring-slate-700 @else text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100 @endif"
                                                >
                                                    <span class="w-full truncate px-0.5 text-[10px] font-semibold leading-tight sm:text-xs">Todos</span>
                                                    <span class="mt-0.5 hidden w-full truncate px-0.5 text-[9px] font-normal text-slate-500 sm:mt-1 sm:block sm:text-[10px] dark:text-slate-400">12 meses</span>
                                                </button>
                                                <button
                                                    type="button"
                                                    wire:click="$set('recurrence_months_filter', 'even')"
                                                    aria-pressed="{{ $recurrence_months_filter === 'even' ? 'true' : 'false' }}"
                                                    class="flex min-h-[3.25rem] min-w-0 flex-1 flex-col items-center justify-center rounded-lg px-1.5 py-2 text-center transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-950 sm:min-h-[3.5rem] sm:px-2 sm:py-2.5 @if ($recurrence_months_filter === 'even') bg-white text-slate-900 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-800 dark:text-slate-100 dark:ring-slate-700 @else text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100 @endif"
                                                >
                                                    <span class="w-full truncate px-0.5 text-[10px] font-semibold leading-tight sm:text-xs">Só pares</span>
                                                    <span class="mt-0.5 hidden w-full truncate px-0.5 text-[9px] font-normal text-slate-500 sm:mt-1 sm:block sm:text-[10px] dark:text-slate-400">fev., abr., jun.…</span>
                                                </button>
                                                <button
                                                    type="button"
                                                    wire:click="$set('recurrence_months_filter', 'odd')"
                                                    aria-pressed="{{ $recurrence_months_filter === 'odd' ? 'true' : 'false' }}"
                                                    class="flex min-h-[3.25rem] min-w-0 flex-1 flex-col items-center justify-center rounded-lg px-1.5 py-2 text-center transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-950 sm:min-h-[3.5rem] sm:px-2 sm:py-2.5 @if ($recurrence_months_filter === 'odd') bg-white text-slate-900 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-800 dark:text-slate-100 dark:ring-slate-700 @else text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100 @endif"
                                                >
                                                    <span class="w-full truncate px-0.5 text-[10px] font-semibold leading-tight sm:text-xs">Só ímpares</span>
                                                    <span class="mt-0.5 hidden w-full truncate px-0.5 text-[9px] font-normal text-slate-500 sm:mt-1 sm:block sm:text-[10px] dark:text-slate-400">jan., mar., mai.…</span>
                                                </button>
                                                <button
                                                    type="button"
                                                    wire:click="$set('recurrence_months_filter', 'list')"
                                                    aria-pressed="{{ $recurrence_months_filter === 'list' ? 'true' : 'false' }}"
                                                    class="flex min-h-[3.25rem] min-w-0 flex-1 flex-col items-center justify-center rounded-lg px-1.5 py-2 text-center transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-950 sm:min-h-[3.5rem] sm:px-2 sm:py-2.5 @if ($recurrence_months_filter === 'list') bg-white text-slate-900 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-800 dark:text-slate-100 dark:ring-slate-700 @else text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100 @endif"
                                                >
                                                    <span class="w-full truncate px-0.5 text-[10px] font-semibold leading-tight sm:text-xs">Escolher meses</span>
                                                    <span class="mt-0.5 hidden w-full truncate px-0.5 text-[9px] font-normal text-slate-500 sm:mt-1 sm:block sm:text-[10px] dark:text-slate-400">Grelha</span>
                                                </button>
                                            </div>
                                        </div>
                                        @error('recurrence_months_filter') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                                        @if ($recurrence_months_filter === 'list')
                                            <div class="mt-3 min-w-0 rounded-xl border border-slate-200 bg-slate-50/80 p-3 dark:border-slate-700 dark:bg-slate-900/40">
                                                <p class="mb-2 text-xs font-semibold text-slate-600 dark:text-slate-300">Marque os meses</p>
                                                {{-- Sempre 6 colunas × 2 linhas (inline style para não depender do bundle CSS) --}}
                                                <div
                                                    class="w-full gap-x-1 gap-y-2 sm:gap-x-2 sm:gap-y-2.5"
                                                    style="display:grid;grid-template-columns:repeat(6,minmax(0,1fr));grid-auto-flow:row;"
                                                >
                                                    @foreach ($recurrenceMonthCells as $mn => $cell)
                                                        <label
                                                            wire:key="recurrence-month-cell-{{ $mn }}"
                                                            title="{{ $cell[1] }}"
                                                            class="flex min-h-[3rem] min-w-0 cursor-pointer flex-col items-center justify-center gap-1 rounded-lg border border-slate-200 bg-white px-0.5 py-1.5 text-center shadow-sm transition hover:border-primary-400 hover:bg-primary-50/70 dark:border-slate-600 dark:bg-slate-950 dark:hover:border-primary-500 dark:hover:bg-primary-950/25 sm:min-h-[3.25rem] sm:py-2"
                                                        >
                                                            <input type="checkbox" wire:model="recurrence_months_list" value="{{ $mn }}" class="m-0 h-3 w-3 shrink-0 cursor-pointer rounded border-slate-300 accent-primary-600 focus:ring-1 focus:ring-primary-500 focus:ring-offset-0 dark:border-slate-500 dark:bg-slate-900" />
                                                            <span class="w-full select-none truncate text-center text-[10px] font-semibold leading-none tracking-tight text-slate-800 dark:text-slate-100 sm:text-xs">{{ $cell[0] }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                            @error('recurrence_months_list') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="min-w-0 space-y-4 rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/60 @if (! $recActive) opacity-60 @endif">
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Fim da série</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Última data em que uma repetição pode cair (inclusive). Limite de repetições geradas: <strong>52</strong> (semanal ou mensal) ou <strong>100</strong> (anual), para além do evento principal.</p>
                            <div>
                                <label class="text-xs font-semibold text-slate-500">Até</label>
                                <input
                                    type="date"
                                    wire:model.live="recurrence_until"
                                    @if (! $recActive) disabled @endif
                                    class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm disabled:cursor-not-allowed dark:border-slate-800 dark:bg-slate-950"
                                />
                                @error('recurrence_until') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            @if ($recurrence_frequency === 'weekly' && $recurrenceSeriesWeekday)
                                <p class="text-xs text-slate-600 dark:text-slate-300">
                                    Dia da semana (definido em «Dados»): <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $recurrenceSeriesWeekday }}</span>
                                </p>
                            @endif
                            @if ($recurrence_frequency === 'monthly_nth')
                                <p class="text-xs text-slate-600 dark:text-slate-300">
                                    A hora de início e fim de cada ocorrência é a mesma que definiu em «Dados»; só muda o dia no calendário.
                                </p>
                            @endif
                            @if ($recurrence_frequency === 'yearly')
                                <p class="text-xs text-slate-600 dark:text-slate-300">
                                    Cada ocorrência mantém a mesma hora de «Dados»; o calendário avança de ano em ano (ou no intervalo que escolheu).
                                </p>
                            @endif
                        </div>
                    </div>

                    @if ($recurrencePreview)
                        <div class="rounded-xl border border-primary-200/80 bg-primary-50/60 px-4 py-3 text-sm text-slate-800 dark:border-primary-900/50 dark:bg-primary-950/30 dark:text-slate-200">
                            <span class="font-semibold text-primary-800 dark:text-primary-200">Resumo</span>
                            @if (($recurrencePreview['type'] ?? '') === 'weekly')
                                <span class="mt-1 block leading-relaxed">
                                    @if ($recurrencePreview['interval_weeks'] === 1)
                                        Todas as semanas
                                    @else
                                        A cada {{ $recurrencePreview['interval_weeks'] }} semanas
                                    @endif
                                    @if (! empty($recurrencePreview['weekday']))
                                        , às <strong>{{ $recurrencePreview['weekday'] }}</strong>
                                    @endif
                                    , entre <strong>{{ $recurrencePreview['start_time'] }}</strong> e <strong>{{ $recurrencePreview['end_time'] }}</strong>,
                                    até <strong>{{ $recurrencePreview['until_formatted'] }}</strong>.
                                    <span class="mt-1 block text-xs text-slate-600 dark:text-slate-400">
                                        Ao guardar: <strong>{{ $recurrencePreview['total_sessions'] }}</strong> datas (1 principal + {{ $recurrencePreview['child_count'] }} repetições), até 52 repetições.
                                    </span>
                                </span>
                            @elseif (($recurrencePreview['type'] ?? '') === 'monthly_nth')
                                <span class="mt-1 block leading-relaxed">
                                    <strong>{{ $recurrencePreview['nth_label'] }}</strong>
                                    <strong>{{ $recurrencePreview['weekday_label'] }}</strong>,
                                    {{ $recurrencePreview['months_summary'] }},
                                    entre <strong>{{ $recurrencePreview['start_time'] }}</strong> e <strong>{{ $recurrencePreview['end_time'] }}</strong>,
                                    até <strong>{{ $recurrencePreview['until_formatted'] }}</strong>.
                                    <span class="mt-1 block text-xs text-slate-600 dark:text-slate-400">
                                        Ao guardar: <strong>{{ $recurrencePreview['total_sessions'] }}</strong> datas (1 principal + {{ $recurrencePreview['child_count'] }} repetições), até 52 repetições.
                                    </span>
                                </span>
                            @elseif (($recurrencePreview['type'] ?? '') === 'yearly')
                                <span class="mt-1 block leading-relaxed">
                                    @if (($recurrencePreview['interval_years'] ?? 1) === 1)
                                        <strong>Anual</strong> (mesma data todos os anos)
                                    @else
                                        A cada <strong>{{ $recurrencePreview['interval_years'] }}</strong> anos, na data de <strong>{{ $recurrencePreview['anchor_formatted'] }}</strong>
                                    @endif
                                    , entre <strong>{{ $recurrencePreview['start_time'] }}</strong> e <strong>{{ $recurrencePreview['end_time'] }}</strong>,
                                    até <strong>{{ $recurrencePreview['until_formatted'] }}</strong>.
                                    <span class="mt-1 block text-xs text-slate-600 dark:text-slate-400">
                                        Ao guardar: <strong>{{ $recurrencePreview['total_sessions'] }}</strong> datas (1 principal + {{ $recurrencePreview['child_count'] }} repetições), até 100 repetições.
                                    </span>
                                </span>
                            @endif
                        </div>
                    @endif
                </div>
            @endif

            @if ($tab === 'structure')
                <p class="text-sm text-slate-600 dark:text-slate-300">Apoio logístico previsto e previsão de público (para secretaria, cantina e infraestrutura).</p>
                <div>
                    <label class="text-xs font-semibold text-slate-500">Previsão de participantes</label>
                    <input type="number" wire:model="expected_attendees" min="0" step="1" placeholder="Opcional" class="mt-1 w-full max-w-xs rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
                    @error('expected_attendees') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <fieldset class="space-y-2 rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                    <legend class="text-xs font-semibold text-slate-500">O evento vai precisar de…</legend>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="needs_sound_controller" class="rounded" /> Controlador de som</label>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="needs_av" class="rounded" /> Audiovisual</label>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="needs_parking" class="rounded" /> Estacionamento</label>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="needs_meals" class="rounded" /> Refeições</label>
                    @if ($needs_meals)
                        <div class="ml-6 space-y-2 border-l-2 border-slate-200 pl-4 dark:border-slate-700">
                            <p class="text-xs font-semibold text-slate-500">Refeções previstas</p>
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="meal_coffee" class="rounded" /> Café</label>
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="meal_lunch" class="rounded" /> Almoço</label>
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="meal_snack" class="rounded" /> Lanche</label>
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="meal_dinner" class="rounded" /> Jantar</label>
                        </div>
                    @endif
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="needs_nursing" class="rounded" /> Enfermagem</label>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="needs_valet" class="rounded" /> Manobrista</label>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="needs_other_materials" class="rounded" /> Outros materiais (especificar)</label>
                    @if ($needs_other_materials)
                        <div class="mt-2">
                            <label class="text-xs font-semibold text-slate-500">Especificação</label>
                            <textarea wire:model="other_materials_note" rows="2" placeholder="Ex.: escadas, cordas, cinto de segurança, maca…" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950"></textarea>
                            @error('other_materials_note') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    @endif
                </fieldset>
            @endif

            @if ($tab === 'people')
                <p class="text-sm text-slate-600 dark:text-slate-300">Papéis por evento: use a edição do evento após gravar (próxima fatia UI) ou gestão dedicada. Por agora, guarde o evento e associe responsáveis numa listagem futura.</p>
            @endif

            <div class="flex flex-wrap gap-2 border-t border-slate-100 pt-4 dark:border-slate-800">
                <x-ui.button type="submit">Guardar</x-ui.button>
                <a href="{{ route('agenda.events.index') }}" wire:navigate class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold dark:border-slate-800">Cancelar</a>
            </div>
        </form>

        @if ($this->event && $this->event->status !== 'cancelled')
            <div class="mt-8 border-t border-slate-100 pt-6 dark:border-slate-800">
                <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-100">Cancelar evento</h3>
                <textarea wire:model="cancel_reason" rows="2" placeholder="Justificativa obrigatória" class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950"></textarea>
                <x-ui.button type="button" variant="danger" class="mt-2" wire:click="cancelEvent">Cancelar evento</x-ui.button>
            </div>
        @endif
    </x-ui.card>
</div>
