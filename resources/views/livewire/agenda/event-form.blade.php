<div class="space-y-6">
    <x-ui.card>
        <form wire:submit="saveDraft" data-progress="marked" class="flex w-full min-w-0 flex-col gap-6">
            <x-nexus.form-progress />
            @if ($tab !== 'main')
                @unless (auth()->user()?->isSuperAdmin())
                    <span hidden data-progress-field data-progress-value="{{ filled($regional_id) ? '1' : '' }}"></span>
                @endunless
                <span hidden data-progress-field data-progress-value="{{ filled($starts_at) ? '1' : '' }}"></span>
                <span hidden data-progress-field data-progress-value="{{ filled($ends_at) ? '1' : '' }}"></span>
            @endif
            <div>
                <label for="event-title" class="text-xs font-semibold text-slate-700 dark:text-slate-200">Título do evento</label>
                <input id="event-title" type="text" wire:model.live.debounce.400ms="title" data-progress-field class="mt-1 min-h-11 w-full rounded-xl border border-slate-200 px-3 py-2 text-base dark:border-slate-800 dark:bg-slate-950" />
                @error('title') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
            </div>

            <div role="tablist" aria-label="Partes do evento" class="grid grid-cols-3 gap-1 border-b border-slate-200 pb-2 dark:border-slate-800 sm:flex sm:flex-nowrap sm:gap-2 sm:overflow-x-auto">
                @foreach (['main' => 'Dados', 'local' => 'Local', 'audience' => 'Público', 'recurrence' => 'Recorrência', 'structure' => 'Estrutura', 'people' => 'Avisos'] as $tabId => $tabLabel)
                    @php
                        $tabErrors = $tabErrorCounts[$tabId] ?? 0;
                    @endphp
                    <button
                        type="button"
                        role="tab"
                        id="tab-{{ $tabId }}"
                        aria-controls="panel-{{ $tabId }}"
                        aria-selected="{{ $tab === $tabId ? 'true' : 'false' }}"
                        @if ($tabErrors > 0) aria-invalid="true" @endif
                        wire:click="$set('tab', '{{ $tabId }}')"
                        @class(['inline-flex min-h-11 shrink-0 flex-wrap items-center justify-center gap-x-2 rounded-lg px-2 text-sm font-semibold sm:justify-start sm:px-3', 'bg-primary-600 text-white' => $tab === $tabId, 'text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-900' => $tab !== $tabId])
                    >
                        <span>{{ $tabLabel }}</span>
                        @if ($tabErrors > 0)
                            <span class="tabular-nums {{ $tab === $tabId ? 'text-white' : 'text-rose-700 dark:text-rose-300' }}">{{ $tabErrors }} {{ $tabErrors === 1 ? 'erro' : 'erros' }}</span>
                        @endif
                    </button>
                @endforeach
            </div>

            @if ($tab === 'main')
                <div id="panel-main" role="tabpanel" aria-labelledby="tab-main" class="grid gap-4 lg:grid-cols-3">
                    <div>
                        <label for="event-regional" class="text-xs font-semibold text-slate-700 dark:text-slate-200">Regional</label>
                        <x-nexus.searchable-select id="event-regional" wire:model.live="regional_id" class="mt-1" empty-label="—" search-placeholder="Buscar regional..." :options="$regionals" />
                        @unless (auth()->user()?->isSuperAdmin())
                            <span hidden data-progress-field data-progress-value="{{ filled($regional_id) ? '1' : '' }}"></span>
                        @endunless
                    </div>
                    <div>
                        <label for="event-type" class="text-xs font-semibold text-slate-700 dark:text-slate-200">Tipo</label>
                        <x-nexus.searchable-select id="event-type" wire:model.live="event_type_id" class="mt-1" empty-label="—" search-placeholder="Buscar tipo..." :options="$eventTypes" />
                    </div>
                    <div>
                        <label for="event-format" class="text-xs font-semibold text-slate-700 dark:text-slate-200">Formato</label>
                        <x-nexus.searchable-select
                            id="event-format"
                            wire:model.live="attendance_mode"
                            class="mt-1"
                            search-placeholder="Buscar formato..."
                            :options="[
                                ['value' => 'in_person', 'label' => 'Presencial'],
                                ['value' => 'online_only', 'label' => 'Somente online'],
                                ['value' => 'hybrid', 'label' => 'Híbrido (presencial e online)'],
                            ]"
                        />
                        @error('attendance_mode') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
                    </div>
                    <div>
                        <label for="event-starts" class="text-xs font-semibold text-slate-700 dark:text-slate-200">Início</label>
                        <input id="event-starts" type="datetime-local" wire:model.live="starts_at" data-progress-field class="mt-1 min-h-11 w-full rounded-xl border border-slate-200 px-3 py-2 text-base dark:border-slate-800 dark:bg-slate-950" />
                        @error('starts_at') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
                    </div>
                    <div>
                        <label for="event-ends" class="text-xs font-semibold text-slate-700 dark:text-slate-200">Fim</label>
                        <input id="event-ends" type="datetime-local" wire:model.live="ends_at" data-progress-field class="mt-1 min-h-11 w-full rounded-xl border border-slate-200 px-3 py-2 text-base dark:border-slate-800 dark:bg-slate-950" />
                        @error('ends_at') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
                    </div>
                    <div>
                        <label for="event-dress" class="text-xs font-semibold text-slate-700 dark:text-slate-200">Traje</label>
                        <x-nexus.searchable-select
                            id="event-dress"
                            wire:model="dress_code"
                            class="mt-1"
                            empty-label="—"
                            search-placeholder="Buscar traje..."
                            :options="[
                                ['value' => 'social', 'label' => 'Social'],
                                ['value' => 'esporte_fino', 'label' => 'Esporte fino'],
                            ]"
                        />
                    </div>
                </div>
            @endif
            @if ($tab === 'people')
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label for="event-attendant" class="text-xs font-semibold text-slate-700 dark:text-slate-200">Quem atende</label>
                            @if (! $regional_id)
                                <p class="mt-1 text-sm text-slate-700 dark:text-slate-200">Selecione a regional do evento para marcar quem atende.</p>
                            @else
                                <x-nexus.searchable-select
                                    id="event-attendant"
                                    wire:model="ministry_servant_id"
                                    class="mt-1"
                                    empty-label="—"
                                    search-placeholder="Buscar pessoa..."
                                    :options="collect($unmatchedAttendantLabel !== '' ? [['value' => 'legacy', 'label' => $unmatchedAttendantLabel.' (nome livre — escolha o cadastro)']] : [])->merge($ministryServants->map(fn ($servant) => ['value' => (string) $servant->id, 'label' => $servant->full_name.' · '.$servant->ministry]))"
                                />
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">A mesma lista das marcações: pessoa do ministério já cadastrada nesta regional.</p>
                            @endif
                            @error('ministry_servant_id') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
                            @if (session('attendant_status'))
                                <x-nexus.inline-message tone="success">{{ session('attendant_status') }}</x-nexus.inline-message>
                            @endif
                        </div>
                        @if ($regional_id && $ministryOffices->isNotEmpty())
                            <details class="rounded-xl border border-slate-200 dark:border-slate-800">
                                <summary class="flex min-h-11 cursor-pointer items-center px-4 text-sm font-semibold text-slate-900 dark:text-slate-100">Cadastrar quem atende</summary>
                                <div class="px-4 pb-4">
                                <div class="mt-3 grid gap-3">
                                    <p class="text-sm text-slate-600 dark:text-slate-300">O cadastro fica na regional, como nas marcações. O celular é opcional. Depois de cadastrar, salve o evento para marcar esta pessoa.</p>
                                    <label for="servant-name" class="text-xs font-semibold text-slate-700 dark:text-slate-200">
                                        Nome completo
                                        <input id="servant-name" type="text" wire:model="servant_name" autocomplete="name" class="mt-1 min-h-11 w-full rounded-xl border border-slate-200 px-3 py-2 text-base font-normal dark:border-slate-800 dark:bg-slate-950" />
                                        @error('servant_name') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
                                    </label>
                                    <label for="servant-position" class="text-xs font-semibold text-slate-700 dark:text-slate-200">
                                        Ministério
                                        <x-nexus.searchable-select id="servant-position" wire:model="servant_position_id" class="mt-1" empty-label="Escolher cargo…" search-placeholder="Buscar cargo..." :options="$ministryOffices" />
                                        @error('servant_position_id') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
                                    </label>
                                    <label for="servant-phone" class="text-xs font-semibold text-slate-700 dark:text-slate-200">
                                        Celular com DDD (opcional)
                                        <input id="servant-phone" type="tel" wire:model="servant_phone" inputmode="tel" placeholder="11 98765-4321" class="mt-1 min-h-11 w-full rounded-xl border border-slate-200 px-3 py-2 text-base font-normal tabular-nums dark:border-slate-800 dark:bg-slate-950" />
                                        @error('servant_phone') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
                                    </label>
                                    <button type="button" wire:click="registerAttendant" class="inline-flex min-h-11 w-fit items-center rounded-xl border border-slate-200 px-3 text-sm font-semibold dark:border-slate-800">
                                        Cadastrar e usar neste evento
                                    </button>
                                </div>
                                </div>
                            </details>
                        @elseif ($regional_id && $ministryServants->isEmpty())
                            <p class="text-sm text-slate-700 dark:text-slate-200">Esta regional ainda não tem cargos de ministério no catálogo. Cadastre Ancião, Diácono e os demais em Ministério antes de marcar quem atende.</p>
                        @endif
                    </div>

                    @php
                        $canPublishNow = auth()->user()?->isSuperAdmin() || auth()->user()?->can('aprovar_evento');
                        $statusLabel = match ($status) {
                            'published' => 'Publicado',
                            'pending_approval' => 'Pendente',
                            'cancelled' => 'Cancelado',
                            'rejected' => 'Rejeitado',
                            default => 'Rascunho',
                        };
                        $statusTone = match ($status) {
                            'published' => 'success',
                            'pending_approval' => 'warning',
                            'cancelled', 'rejected' => 'danger',
                            default => 'neutral',
                        };
                    @endphp
                    <fieldset class="grid gap-3 border-t border-slate-200 pt-4 md:col-span-2 dark:border-slate-800">
                        <legend class="text-sm font-semibold text-slate-900 dark:text-slate-100">Publicar</legend>
                        <p class="text-sm text-slate-700 dark:text-slate-200">
                            @if ($canPublishNow)
                                Publicar coloca o evento na lista de batismos quando essa opção está marcada, e envia o aviso no WhatsApp quando o aviso está ativo.
                            @else
                                Sem permissão para publicar, o pedido fica pendente. A lista de batismos e o WhatsApp seguem as opções marcadas.
                            @endif
                        </p>
                        <p class="text-sm text-slate-700 dark:text-slate-200">
                            Situação atual:
                            <x-ui.badge :tone="$statusTone">{{ $statusLabel }}</x-ui.badge>
                        </p>
                        <label class="flex min-h-11 items-center gap-2 text-sm">
                            <input type="checkbox" wire:model="include_on_baptism_list" class="rounded" />
                            Entra na lista de batismos
                        </label>
                        <label class="flex min-h-11 items-center gap-2 text-sm">
                            <input type="checkbox" wire:model.live="whatsapp_enabled" class="rounded" />
                            Envia aviso no WhatsApp
                        </label>
                        @error('whatsapp_enabled') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
                        <div class="@if (! $whatsapp_enabled) opacity-60 @endif">
                            <label for="event-wa-template" class="text-xs font-semibold text-slate-700 dark:text-slate-200">Modelo do WhatsApp</label>
                            <x-nexus.searchable-select
                                id="event-wa-template"
                                wire:model="whatsapp_notice_template_id"
                                class="mt-1"
                                empty-label="—"
                                search-placeholder="Buscar modelo..."
                                :disabled="! $whatsapp_enabled"
                                :options="\App\Models\WhatsAppNoticeTemplate::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get()"
                            />
                            @error('whatsapp_notice_template_id') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
                        </div>
                    </fieldset>

                    <details
                        class="rounded-xl border border-slate-200 md:col-span-2 dark:border-slate-800"
                        wire:ignore.self
                        wire:key="event-description-panel"
                        x-data
                        x-init="if (window.__eventFormDescriptionOpen) $el.open = true"
                        x-on:toggle="window.__eventFormDescriptionOpen = $el.open"
                    >
                        <summary class="flex min-h-11 cursor-pointer items-center px-4 text-sm font-semibold text-slate-900 dark:text-slate-100">Descrição e modelo</summary>
                        <div class="grid gap-4 px-4 pb-4">
                            <p class="text-sm text-slate-600 dark:text-slate-300">O texto guarda os códigos do modelo. A pré-visualização muda quando a data, a hora, o local, a regional ou o título mudam.</p>
                            <x-ui.simple-editor model="description" :value="$description" :fields="$placeholderFields" live placeholder="Descrição do evento" />
                            @error('description') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
                            @if ($descriptionPreview !== '')
                                <div>
                                    <div class="text-sm font-semibold text-slate-800 dark:text-slate-100">Como fica neste evento</div>
                                    <div class="mt-1 text-sm text-slate-800 dark:text-slate-100 [&_b]:font-bold [&_em]:italic [&_i]:italic [&_ol]:list-decimal [&_ol]:pl-5 [&_strong]:font-bold [&_u]:underline [&_ul]:my-1 [&_ul]:list-disc [&_ul]:pl-5">{!! \App\Support\SimpleRichText::toHtml($descriptionPreview) !!}</div>
                                </div>
                            @endif
                            @if ($event_type_id && $can_edit_type_template)
                                <div>
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <p class="text-sm font-semibold">Modelo deste tipo</p>
                                        <a href="{{ route('agenda.event-type-templates') }}" wire:navigate class="text-sm font-semibold text-primary-700 dark:text-primary-300">Área de modelos</a>
                                    </div>
                                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Vale para os eventos deste tipo que ainda usam o texto anterior.</p>
                                    <div class="mt-2" wire:key="type-template-editor-{{ $event_type_id }}">
                                        <x-ui.simple-editor model="type_template" :value="$type_template" :fields="$placeholderFields" placeholder="Texto do modelo deste tipo" body-class="min-h-48" />
                                    </div>
                                    @error('type_template') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
                                    @if (session('template_status'))
                                        <x-nexus.inline-message tone="success">{{ session('template_status') }}</x-nexus.inline-message>
                                    @endif
                                    <button type="button" wire:click="saveTypeTemplate" class="mt-2 min-h-11 rounded-xl border border-slate-200 px-3 text-sm font-semibold dark:border-slate-800">
                                        Salvar modelo do tipo
                                    </button>
                                </div>
                            @endif
                        </div>
                    </details>
            @endif

            @if ($tab === 'local')
                <div id="panel-local" role="tabpanel" aria-labelledby="tab-local" class="grid gap-4 md:grid-cols-2">
                @if ($attendance_mode === 'online_only')
                    <p class="text-sm text-amber-800 md:col-span-2 dark:text-amber-200">Somente online: o local e a sala são limpos ao salvar.</p>
                @else
                    <div>
                        <label for="event-location" class="text-xs font-semibold text-slate-700 dark:text-slate-200">Local</label>
                        <x-nexus.searchable-select id="event-location" wire:model.live="location_id" class="mt-1" empty-label="—" search-placeholder="Buscar local..." :options="$locations" />
                    </div>
                    <div>
                        <label for="event-room" class="text-xs font-semibold text-slate-700 dark:text-slate-200">Sala</label>
                        <x-nexus.searchable-select id="event-room" wire:model="meeting_room_id" class="mt-1" empty-label="—" search-placeholder="Buscar sala..." :options="$meetingRooms" />
                        @error('meeting_room_id') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
                        @if ($meetingRooms->isEmpty())
                            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Nenhuma sala ativa neste contexto. <a href="{{ route('infrastructure.meeting-rooms.index') }}" wire:navigate class="font-semibold text-primary-700 dark:text-primary-300">Ver salas</a></p>
                        @endif
                    </div>
                @endif
                </div>
            @endif

            @if ($tab === 'audience')
                <div id="panel-audience" role="tabpanel" aria-labelledby="tab-audience">
                    @include('livewire.agenda.partials.event-audience-picker')
                </div>
            @endif

            @if ($tab === 'recurrence')
                @php
                    $recActive = in_array($recurrence_frequency, ['weekly', 'monthly_nth', 'yearly'], true);
                @endphp
                <div id="panel-recurrence" role="tabpanel" aria-labelledby="tab-recurrence" class="grid gap-8">
                    <div class="grid min-w-0 gap-8">
                        <div class="min-w-0 space-y-4">
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Padrão de repetição</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Único, semanal, mensal por dia da semana no mês (ex.: 2.ª sexta) ou anual / bienal na mesma data de «Dados».</p>
                            <div class="grid w-full grid-cols-2 gap-1.5 rounded-xl bg-slate-100 p-1.5 sm:grid-cols-4 dark:bg-slate-900" role="group" aria-label="{{ __('Padrão de repetição') }}">
                                <button
                                    type="button"
                                    wire:click="$set('recurrence_frequency', null)"
                                    aria-pressed="{{ ! $recurrence_frequency ? 'true' : 'false' }}"
                                    class="flex min-h-11 min-w-0 items-center justify-center rounded-lg px-2 py-2 text-center text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-950 sm:text-sm @if (! $recurrence_frequency) bg-white text-slate-900 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-800 dark:text-slate-100 dark:ring-slate-700 @else text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100 @endif"
                                ><span>Único</span></button>
                                <button
                                    type="button"
                                    wire:click="$set('recurrence_frequency', 'weekly')"
                                    aria-pressed="{{ $recurrence_frequency === 'weekly' ? 'true' : 'false' }}"
                                    class="flex min-h-11 min-w-0 items-center justify-center rounded-lg px-2 py-2 text-center text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-950 sm:text-sm @if ($recurrence_frequency === 'weekly') bg-white text-slate-900 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-800 dark:text-slate-100 dark:ring-slate-700 @else text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100 @endif"
                                ><span>Semanal</span></button>
                                <button
                                    type="button"
                                    wire:click="$set('recurrence_frequency', 'monthly_nth')"
                                    aria-pressed="{{ $recurrence_frequency === 'monthly_nth' ? 'true' : 'false' }}"
                                    class="flex min-h-11 min-w-0 items-center justify-center rounded-lg px-2 py-2 text-center text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-950 sm:text-sm @if ($recurrence_frequency === 'monthly_nth') bg-white text-slate-900 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-800 dark:text-slate-100 dark:ring-slate-700 @else text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100 @endif"
                                ><span>Mensal</span></button>
                                <button
                                    type="button"
                                    wire:click="$set('recurrence_frequency', 'yearly')"
                                    aria-pressed="{{ $recurrence_frequency === 'yearly' ? 'true' : 'false' }}"
                                    class="flex min-h-11 min-w-0 items-center justify-center rounded-lg px-2 py-2 text-center text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-950 sm:text-sm @if ($recurrence_frequency === 'yearly') bg-white text-slate-900 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-800 dark:text-slate-100 dark:ring-slate-700 @else text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100 @endif"
                                ><span>Anual</span></button>
                            </div>
                            @error('recurrence_frequency') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror

                            @if ($recurrence_frequency === 'weekly')
                                <div class="border-t border-slate-100 pt-4 dark:border-slate-800">
                                    <label class="text-xs font-semibold text-slate-500">Intervalo entre semanas</label>
                                    <x-nexus.searchable-select
                                        wire:model.live="recurrence_interval_weeks"
                                        class="mt-1"
                                        search-placeholder="Buscar intervalo..."
                                        :options="collect(range(1, 12))->map(fn ($w) => ['value' => (string) $w, 'label' => $w === 1 ? 'Todas as semanas' : 'A cada '.$w.' semanas'])"
                                    />
                                    @error('recurrence_interval_weeks') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
                                </div>
                            @endif

                            @if ($recurrence_frequency === 'yearly')
                                <div class="border-t border-slate-100 pt-4 dark:border-slate-800">
                                    <label class="text-xs font-semibold text-slate-500">Intervalo</label>
                                    <x-nexus.searchable-select
                                        wire:model.live="recurrence_interval_years"
                                        class="mt-1"
                                        search-placeholder="Buscar intervalo..."
                                        :options="collect(range(1, 20))->map(fn ($y) => ['value' => (string) $y, 'label' => $y === 1 ? 'Todos os anos (anual)' : 'A cada '.$y.' anos'])"
                                    />
                                    @error('recurrence_interval_years') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
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
                                            <x-nexus.searchable-select
                                                wire:model.live="recurrence_monthly_nth"
                                                class="mt-1"
                                                search-placeholder="Buscar ordem..."
                                                :options="[
                                                    ['value' => '1', 'label' => '1.ª'],
                                                    ['value' => '2', 'label' => '2.ª'],
                                                    ['value' => '3', 'label' => '3.ª'],
                                                    ['value' => '4', 'label' => '4.ª'],
                                                    ['value' => '5', 'label' => '5.ª'],
                                                    ['value' => '-1', 'label' => 'Última do mês'],
                                                ]"
                                            />
                                            @error('recurrence_monthly_nth') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
                                        </div>
                                        <div>
                                            <label class="text-xs font-semibold text-slate-500">Dia da semana</label>
                                            <x-nexus.searchable-select
                                                wire:model.live="recurrence_monthly_weekday"
                                                class="mt-1"
                                                search-placeholder="Buscar dia..."
                                                :options="[
                                                    ['value' => '0', 'label' => 'Domingo'],
                                                    ['value' => '1', 'label' => 'Segunda-feira'],
                                                    ['value' => '2', 'label' => 'Terça-feira'],
                                                    ['value' => '3', 'label' => 'Quarta-feira'],
                                                    ['value' => '4', 'label' => 'Quinta-feira'],
                                                    ['value' => '5', 'label' => 'Sexta-feira'],
                                                    ['value' => '6', 'label' => 'Sábado'],
                                                ]"
                                            />
                                            @error('recurrence_monthly_weekday') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold text-slate-500">Meses em que aplica</p>
                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Um toque por opção. Em «Escolher meses» use a grelha abaixo (6 meses por linha).</p>
                                        <div class="mt-3 rounded-xl bg-slate-100 p-1.5 dark:bg-slate-900" role="group" aria-label="{{ __('Meses em que aplica') }}">
                                            <div class="grid w-full grid-cols-2 gap-1.5 sm:grid-cols-4">
                                                <button
                                                    type="button"
                                                    wire:click="$set('recurrence_months_filter', 'all')"
                                                    aria-pressed="{{ $recurrence_months_filter === 'all' ? 'true' : 'false' }}"
                                                    class="flex min-h-[3.25rem] min-w-0 flex-1 flex-col items-center justify-center rounded-lg px-1.5 py-2 text-center transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-950 sm:min-h-[3.5rem] sm:px-2 sm:py-2.5 @if ($recurrence_months_filter === 'all') bg-white text-slate-900 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-800 dark:text-slate-100 dark:ring-slate-700 @else text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100 @endif"
                                                >
                                                    <span class="w-full truncate px-0.5 text-[10px] font-semibold leading-tight sm:text-xs">{{ __('Todos') }}</span>
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
                                        @error('recurrence_months_filter') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
                                        @if ($recurrence_months_filter === 'list')
                                            <div class="mt-3 min-w-0 rounded-xl border border-slate-200 bg-slate-50/80 p-3 dark:border-slate-700 dark:bg-slate-900/40">
                                                <p class="mb-2 text-xs font-semibold text-slate-600 dark:text-slate-300">Marque os meses</p>
                                                {{-- Sempre 6 colunas × 2 linhas (inline style para não depender do bundle CSS) --}}
                                                <div class="grid w-full grid-cols-3 gap-2 sm:grid-cols-6">
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
                                            @error('recurrence_months_list') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="min-w-0 space-y-4 border-t border-slate-200 pt-8 dark:border-slate-800 @if (! $recActive) opacity-60 @endif">
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Fim da série</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Última data em que uma repetição pode cair (inclusive). Limite de repetições geradas: <strong>52</strong> (semanal ou mensal) ou <strong>100</strong> (anual), para além do evento principal.</p>
                            <div>
                                <label class="text-xs font-semibold text-slate-500">Até</label>
                                <input
                                    type="date"
                                    wire:model.live="recurrence_until"
                                    @if (! $recActive) disabled @endif
                                    class="mt-1 min-h-11 w-full rounded-xl border border-slate-200 px-3 py-2 text-base disabled:cursor-not-allowed dark:border-slate-800 dark:bg-slate-950"
                                />
                                @error('recurrence_until') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
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
                <div id="panel-structure" role="tabpanel" aria-labelledby="tab-structure" class="grid gap-6">
                <p class="text-sm text-slate-600 dark:text-slate-300">Apoio logístico previsto e previsão de público (para secretaria, cantina e infraestrutura).</p>
                <div>
                    <label for="event-expected" class="text-xs font-semibold text-slate-700 dark:text-slate-200">Previsão de participantes</label>
                    <input id="event-expected" type="number" wire:model="expected_attendees" min="0" step="1" placeholder="Opcional" class="mt-1 min-h-11 w-full max-w-xs rounded-xl border border-slate-200 px-3 py-2 text-base dark:border-slate-800 dark:bg-slate-950" />
                    @error('expected_attendees') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
                </div>
                <fieldset class="rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                    <legend class="px-1 text-sm font-semibold text-slate-900 dark:text-slate-100">O evento vai precisar de…</legend>
                    <div class="grid gap-1 sm:grid-cols-2 lg:grid-cols-3">
                    <label class="flex min-h-11 items-center gap-2 text-sm"><input type="checkbox" wire:model="needs_sound_controller" class="rounded" /> Controlador de som</label>
                    <label class="flex min-h-11 items-center gap-2 text-sm"><input type="checkbox" wire:model="needs_av" class="rounded" /> Audiovisual</label>
                    <label class="flex min-h-11 items-center gap-2 text-sm"><input type="checkbox" wire:model="needs_parking" class="rounded" /> Estacionamento</label>
                    <label class="flex min-h-11 items-center gap-2 text-sm"><input type="checkbox" wire:model="needs_meals" class="rounded" /> Refeições</label>
                    <label class="flex min-h-11 items-center gap-2 text-sm"><input type="checkbox" wire:model="needs_nursing" class="rounded" /> Enfermagem</label>
                    <label class="flex min-h-11 items-center gap-2 text-sm"><input type="checkbox" wire:model="needs_valet" class="rounded" /> Manobrista</label>
                    <label class="flex min-h-11 items-center gap-2 text-sm"><input type="checkbox" wire:model="has_libras" class="rounded" /> Intérprete de Libras</label>
                    <label class="flex min-h-11 items-center gap-2 text-sm"><input type="checkbox" wire:model="needs_other_materials" class="rounded" /> Outros materiais (especificar)</label>
                    </div>
                    @if ($needs_meals)
                        <p class="mt-3 text-sm font-semibold text-slate-800 dark:text-slate-100">Refeições previstas</p>
                        <div class="grid gap-1 sm:grid-cols-2 md:grid-cols-4">
                            <label class="flex min-h-11 items-center gap-2 text-sm"><input type="checkbox" wire:model="meal_coffee" class="rounded" /> Café</label>
                            <label class="flex min-h-11 items-center gap-2 text-sm"><input type="checkbox" wire:model="meal_lunch" class="rounded" /> Almoço</label>
                            <label class="flex min-h-11 items-center gap-2 text-sm"><input type="checkbox" wire:model="meal_snack" class="rounded" /> Lanche</label>
                            <label class="flex min-h-11 items-center gap-2 text-sm"><input type="checkbox" wire:model="meal_dinner" class="rounded" /> Jantar</label>
                        </div>
                    @endif
                    @if ($needs_other_materials)
                        <div class="mt-2">
                            <label class="text-xs font-semibold text-slate-500">Especificação</label>
                            <textarea wire:model="other_materials_note" rows="2" placeholder="Ex.: escadas, cordas, cinto de segurança, maca…" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950"></textarea>
                            @error('other_materials_note') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
                        </div>
                    @endif
                </fieldset>
                </div>
            @endif

            @if ($tab === 'people')
                <div id="panel-people" role="tabpanel" aria-labelledby="tab-people" class="grid min-w-0 gap-4 rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Grupos do aviso</p>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Estes grupos recebem o texto quando o aviso no WhatsApp está ligado.</p>
                        @unless ($whatsapp_enabled)
                            <p class="mt-1 text-sm text-slate-700 dark:text-slate-200">O aviso está desligado. A escolha fica guardada e só vale quando ele for ligado.</p>
                        @endunless
                    </div>
                    @if ($whatsappGroups->isNotEmpty())
                        <div @class(['grid min-w-0 gap-1 sm:grid-cols-2', 'opacity-60' => ! $whatsapp_enabled])>
                            @foreach ($whatsappGroups as $g)
                                <label class="flex min-h-11 items-center gap-2 text-sm">
                                    <input type="checkbox" wire:model="whatsapp_group_ids" value="{{ $g->id }}" class="rounded" />
                                    {{ $g->name }}
                                </label>
                            @endforeach
                        </div>
                    @endif
                    <div>
                        <button type="button" wire:click="applyAudiencePreset" class="inline-flex min-h-11 items-center rounded-xl border border-slate-200 px-3 text-sm font-semibold dark:border-slate-800">
                            Aplicar público do modelo
                        </button>
                        @error('audience_preset') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
                    </div>
                </div>
            @endif

            @php($canPublishNow = auth()->user()?->isSuperAdmin() || auth()->user()?->can('aprovar_evento'))
            <div class="flex flex-wrap items-center gap-2 border-t border-slate-200 pt-6 dark:border-slate-800">
                @if ($status !== 'published')
                    <x-ui.button type="button" wire:click="publish">{{ $canPublishNow ? 'Publicar' : 'Enviar para aprovação' }}</x-ui.button>
                    <x-ui.button type="submit" variant="secondary">Salvar rascunho</x-ui.button>
                @else
                    <x-ui.button type="submit">Salvar</x-ui.button>
                @endif
                <a href="{{ route('agenda.events.index') }}" wire:navigate class="inline-flex min-h-11 items-center rounded-xl border border-slate-200 px-4 text-sm font-semibold dark:border-slate-800">Voltar à agenda</a>
            </div>
        </form>

        @if ($this->event && $this->event->status !== 'cancelled')
            <div class="mx-auto mt-8 max-w-3xl border-t border-slate-200 pt-8 dark:border-slate-800">
                <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-100">Cancelar {{ $this->event->title }}</h3>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">O evento sai da agenda. A justificativa fica registrada.</p>
                <label for="cancel-reason" class="mt-3 block text-xs font-semibold text-slate-700 dark:text-slate-200">Justificativa</label>
                <textarea id="cancel-reason" wire:model="cancel_reason" rows="2" class="mt-1 w-full min-h-11 rounded-xl border border-slate-200 px-3 py-2 text-base dark:border-slate-800 dark:bg-slate-950"></textarea>
                @error('cancel_reason') <x-nexus.inline-message>Escreva a justificativa para cancelar.</x-nexus.inline-message> @enderror
                <x-ui.button type="button" variant="danger" class="mt-2" wire:click="cancelEvent" wire:confirm="Cancelar {{ $this->event->title }}? O evento sai da agenda.">Cancelar {{ $this->event->title }}</x-ui.button>
            </div>
        @endif
    </x-ui.card>
</div>
