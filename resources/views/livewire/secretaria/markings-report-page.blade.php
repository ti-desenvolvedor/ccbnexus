<div class="min-w-0 space-y-8">
    <header class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div class="max-w-[68ch] space-y-2">
            <h1 class="text-balance text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-50">Relatório de Marcações</h1>
            <p class="text-sm leading-6 text-slate-700 dark:text-slate-300">
                Quem atende, de {{ $from->format('d/m/Y') }} a {{ $to->format('d/m/Y') }}.
                A lista segue o tipo de evento e, dentro de cada tipo, a data.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <p class="mr-2 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $missingCount }} sem quem atende</p>
            <button type="button" wire:click="openRegistry" class="inline-flex min-h-11 items-center rounded-xl bg-primary-700 px-4 text-sm font-semibold text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">Cadastrar quem atende</button>
            <a href="{{ route('secretaria.markings.export', ['format' => 'xlsx'] + $exportQuery) }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100">Excel</a>
            <a href="{{ route('secretaria.markings.export', ['format' => 'pdf'] + $exportQuery) }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100">PDF</a>
        </div>
    </header>

    <section aria-label="Filtros" class="space-y-5">
        <fieldset>
            <legend class="text-sm font-semibold text-slate-800 dark:text-slate-100">Tipos de evento</legend>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($markingTypes as $type)
                    @php $selected = in_array($type['slug'], $event_type_slugs, true); @endphp
                    <button
                        type="button"
                        wire:key="marking-type-{{ $type['slug'] }}"
                        wire:click="toggleDefaultType(@js($type['slug']))"
                        aria-pressed="{{ $selected ? 'true' : 'false' }}"
                        class="inline-flex min-h-11 items-center gap-2 whitespace-nowrap rounded-full border px-3.5 text-sm transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-50 dark:focus-visible:ring-offset-slate-950 {{ $selected ? 'border-primary-700 bg-primary-700 font-semibold text-white hover:border-primary-800 hover:bg-primary-800 dark:border-primary-500 dark:bg-primary-600 dark:hover:border-primary-500 dark:hover:bg-primary-500' : 'border-slate-300 bg-white font-medium text-slate-600 hover:border-slate-400 hover:bg-slate-100 hover:text-slate-900 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-400 dark:hover:border-slate-500 dark:hover:bg-slate-900 dark:hover:text-slate-100' }}"
                    >
                        @if ($selected)
                            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                <path stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        @endif
                        {{ $type['name'] }}
                    </button>
                @endforeach
            </div>
        </fieldset>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 lg:gap-4">
            <label for="markings-other-type" class="text-sm font-semibold text-slate-800 dark:text-slate-100">
                Outros eventos
                <x-nexus.searchable-select
                    id="markings-other-type"
                    wire:model.live="other_slug"
                    class="mt-1"
                    empty-label="Adicionar tipo…"
                    search-placeholder="Buscar tipo..."
                    :options="$otherTypes->map(fn ($type) => ['value' => $type->slug, 'label' => $type->name, 'disabled' => in_array($type->slug, $extra_slugs, true)])"
                />
            </label>
            <label for="markings-date-from" class="text-sm font-semibold text-slate-800 dark:text-slate-100">
                De
                <input id="markings-date-from" type="date" wire:model.live="date_from" min="{{ $today }}" class="mt-1 block min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm tabular-nums text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100" />
            </label>
            <label for="markings-date-to" class="text-sm font-semibold text-slate-800 dark:text-slate-100">
                Até
                <input id="markings-date-to" type="date" wire:model.live="date_to" min="{{ $today }}" class="mt-1 block min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm tabular-nums text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100" />
            </label>
            <label for="markings-status" class="text-sm font-semibold text-slate-800 dark:text-slate-100">
                Situação
                <x-nexus.searchable-select
                    id="markings-status"
                    wire:model.live="filter"
                    class="mt-1"
                    search-placeholder="Buscar situação..."
                    :options="[
                        ['value' => 'all', 'label' => 'Todos'],
                        ['value' => 'missing', 'label' => 'Falta marcar'],
                        ['value' => 'ok', 'label' => 'Marcados (ok)'],
                    ]"
                />
            </label>
        </div>

        @if ($extraTypes->isNotEmpty())
            <div class="flex flex-wrap gap-2">
                @foreach ($extraTypes as $type)
                    <button type="button" wire:click="removeExtra('{{ $type['slug'] }}')" class="inline-flex min-h-11 items-center gap-2 rounded-full border border-slate-300 bg-slate-100 px-3 text-sm font-semibold text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">
                        {{ $type['name'] }}
                        <span>Remover</span>
                    </button>
                @endforeach
            </div>
        @endif
    </section>

    <div class="grid grid-cols-1">
        <div class="space-y-4 md:hidden">
            @forelse ($groups as $group)
                <section class="space-y-2">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-50">{{ $group['name'] }}</h2>
                    @foreach ($group['rows'] as $row)
                        <article wire:key="marking-card-{{ $row['event_id'] }}" class="space-y-2 rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold tabular-nums text-slate-900 dark:text-slate-100">{{ $row['when'] }}</p>
                                    <p class="text-sm text-slate-700 dark:text-slate-300">{{ $row['type_name'] }} · {{ $row['place'] }}</p>
                                </div>
                                @if ($row['marked'])
                                    <span class="shrink-0 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-950 dark:bg-emerald-950 dark:text-emerald-100">OK</span>
                                @else
                                    <span class="shrink-0 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-950 dark:bg-amber-950 dark:text-amber-100">Falta marcar</span>
                                @endif
                            </div>
                            @if ($row['attendant'] !== '')
                                <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $row['attendant'] }}@if (($row['ministry'] ?? '') !== '') <span class="font-normal text-slate-700 dark:text-slate-300">· {{ $row['ministry'] }}</span>@endif</p>
                            @endif
                            <div class="flex flex-col gap-2 sm:flex-row">
                                <button type="button" wire:click="openAttendant({{ $row['event_id'] }})" class="inline-flex min-h-11 flex-1 items-center justify-center rounded-xl bg-primary-700 px-4 text-sm font-semibold text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">{{ $row['marked'] ? 'Trocar' : 'Cadastrar' }}</button>
                                <a href="{{ route('agenda.events.edit', $row['edit_id']) }}" wire:navigate class="inline-flex min-h-11 flex-1 items-center justify-center rounded-xl border border-slate-300 px-4 text-sm font-semibold text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-slate-600 dark:text-slate-100">Abrir</a>
                            </div>
                        </article>
                    @endforeach
                </section>
            @empty
                <p class="rounded-xl border border-slate-200 px-4 py-10 text-center text-sm text-slate-700 dark:border-slate-800 dark:text-slate-300">Nenhuma marcação neste período para os tipos selecionados.</p>
            @endforelse
        </div>
        <div class="hidden overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800 md:block">
            <table class="w-max min-w-full border-separate border-spacing-0 text-sm">
                <thead>
                    <tr class="bg-slate-100 text-left text-slate-800 dark:bg-slate-900 dark:text-slate-100">
                        <th scope="col" class="px-4 py-3 font-semibold">Data</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Tipo</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Local</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Quem atende</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Estado</th>
                        <th scope="col" class="px-4 py-3 font-semibold"><span class="sr-only">Ações</span></th>
                    </tr>
                </thead>
                <tbody>
                    @php $stripe = 0; @endphp
                    @forelse ($groups as $group)
                        <tr class="bg-slate-200 text-slate-900 dark:bg-slate-800 dark:text-slate-50">
                            <th scope="rowgroup" colspan="6" class="px-4 py-2 text-left text-sm font-semibold">{{ $group['name'] }}</th>
                        </tr>
                        @foreach ($group['rows'] as $row)
                            @php $stripe++; @endphp
                            <tr wire:key="marking-{{ $row['event_id'] }}" class="{{ $stripe % 2 === 0 ? 'bg-slate-100 dark:bg-slate-900' : 'bg-white dark:bg-slate-950' }}">
                                <td class="whitespace-nowrap px-4 py-3 tabular-nums text-slate-900 dark:text-slate-100">{{ $row['when'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-800 dark:text-slate-200">{{ $row['type_name'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-800 dark:text-slate-200">{{ $row['place'] }}</td>
                                <td class="px-4 py-3 text-slate-800 dark:text-slate-200">
                                    <div class="flex flex-col items-start gap-1">
                                        @if ($row['attendant'] !== '')
                                            <span class="font-semibold text-slate-900 dark:text-slate-100">{{ $row['attendant'] }}</span>
                                        @endif
                                        @if (($row['ministry'] ?? '') !== '')
                                            <span class="text-slate-700 dark:text-slate-300">{{ $row['ministry'] }}</span>
                                        @endif
                                        <button type="button" wire:click="openAttendant({{ $row['event_id'] }})" class="inline-flex min-h-11 items-center font-semibold text-primary-700 underline decoration-primary-700/40 underline-offset-4 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:text-primary-300">
                                            {{ $row['marked'] ? 'Trocar' : 'Cadastrar' }}
                                        </button>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    @if ($row['marked'])
                                        <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-950 dark:bg-emerald-950 dark:text-emerald-100">OK</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-950 dark:bg-amber-950 dark:text-amber-100">Falta marcar</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('agenda.events.edit', $row['edit_id']) }}" wire:navigate class="inline-flex min-h-11 items-center font-semibold text-primary-700 underline decoration-primary-700/40 underline-offset-4 hover:decoration-primary-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:text-primary-300 dark:decoration-primary-300/40">Abrir</a>
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr class="bg-white dark:bg-slate-950">
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-700 dark:text-slate-300">Nenhuma marcação neste período para os tipos selecionados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if (session('servant_status'))
        <x-nexus.inline-message tone="success" class="font-semibold">{{ session('servant_status') }}</x-nexus.inline-message>
    @endif

    <x-modal name="marking-attendant" :show="$errors->isNotEmpty()" focusable maxWidth="lg">
        <form wire:submit="saveServant" data-progress="marked" class="space-y-5 p-6">
            <div class="flex items-start gap-3">
                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-primary-50 text-primary-700 dark:bg-slate-800 dark:text-primary-100">
                    <x-nexus.glyph name="person" class="h-5 w-5" />
                </span>
                <div class="min-w-0 space-y-1">
                <h2 class="text-lg font-semibold text-slate-900">Quem atende</h2>
                @if ($markingEvent)
                    <p class="text-sm text-slate-700">
                        {{ $markingEvent->starts_at?->format('d/m/Y H:i') }}
                        · {{ $markingEvent->eventType?->name }}
                        · {{ $markingEvent->location?->name ?? $markingEvent->title }}
                    </p>
                @else
                    <p class="text-sm text-slate-700">O cadastro fica na regional. O celular é opcional. Para marcar um evento, escolha a pessoa em Já cadastrado.</p>
                @endif
                </div>
            </div>

            <x-nexus.form-progress />

            @if (! $canRegisterServant)
                <p class="text-sm text-slate-700">Selecione uma regional no contexto para cadastrar quem atende.</p>
            @elseif ($ministryOffices->isEmpty())
                <p class="text-sm text-slate-700">Esta regional ainda não tem cargos de ministério no catálogo. Cadastre Ancião, Diácono e os demais em Ministério antes de marcar quem atende.</p>
            @else
                @if ($servants->isNotEmpty())
                    <div class="space-y-2 border-b border-slate-200 pb-5">
                        <label for="existing-servant" class="text-sm font-semibold text-slate-800">Já cadastrado</label>
                        <x-nexus.searchable-select
                            id="existing-servant"
                            wire:model="existing_servant_id"
                            class="mt-1"
                            empty-label="Escolher pessoa…"
                            search-placeholder="Buscar pessoa..."
                            :options="$servants->map(fn ($servant) => ['value' => (string) $servant->id, 'label' => $servant->full_name.' · '.$servant->ministry])"
                        />
                        @error('existing_servant_id') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
                        @if ($markingEvent)
                            <button type="button" wire:click="useExistingServant" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 px-4 text-sm font-semibold text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">Marcar este no evento</button>
                        @endif
                    </div>
                @endif

                <div class="space-y-3">
                    <p class="text-sm font-semibold text-slate-900">Novo cadastro</p>
                    <label for="servant-name" class="block text-sm font-semibold text-slate-800">
                        Nome completo
                        <input id="servant-name" type="text" wire:model="servant_name" data-progress-field autocomplete="name" class="mt-1 block min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-normal text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500" />
                        @error('servant_name') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
                    </label>
                    <label for="servant-position" class="block text-sm font-semibold text-slate-800">
                        Ministério
                        <x-nexus.searchable-select id="servant-position" wire:model="servant_position_id" data-progress-field class="mt-1" empty-label="Escolher cargo…" search-placeholder="Buscar cargo..." :options="$ministryOffices" />
                        @error('servant_position_id') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
                    </label>
                    <label for="servant-phone" class="block text-sm font-semibold text-slate-800">
                        Celular com DDD (opcional)
                        <input id="servant-phone" type="tel" wire:model="servant_phone" inputmode="tel" placeholder="11 98765-4321" class="mt-1 block min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-normal tabular-nums text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500" />
                        @error('servant_phone') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
                    </label>
                </div>
            @endif

            <div class="flex justify-end gap-2">
                <button type="button" x-on:click="$dispatch('close')" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 px-4 text-sm font-semibold text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">Cancelar</button>
                @if ($canRegisterServant && $ministryOffices->isNotEmpty())
                    <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-primary-700 px-4 text-sm font-semibold text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">Salvar cadastro</button>
                    @if ($markingEvent)
                        <button type="button" wire:click="saveAndAssign" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 px-4 text-sm font-semibold text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">Salvar e marcar neste evento</button>
                    @endif
                @endif
            </div>
        </form>
    </x-modal>
</div>
