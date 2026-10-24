<div class="space-y-4">
    <div class="flex flex-col gap-4">
        <div class="flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-semibold text-slate-900 dark:text-slate-100">Lista de Batismos e Diversos</h1>
                <p class="text-sm text-slate-600 dark:text-slate-300">
                    Contexto:
                    <span class="font-semibold text-slate-900 dark:text-slate-100">{{ $regional?->name ?? __('nenhuma regional selecionada') }}</span>
                </p>
            </div>
            <div class="flex flex-wrap gap-2" role="tablist" aria-label="Modos da lista">
                @foreach (['listagem' => 'Listagem', 'visualizacao' => 'Visualização', 'versoes' => 'Versões'] as $key => $label)
                    <button
                        type="button"
                        role="tab"
                        wire:click="setScreen('{{ $key }}')"
                        aria-selected="{{ $screen === $key ? 'true' : 'false' }}"
                        class="inline-flex min-h-11 max-w-full items-center justify-center whitespace-normal rounded-xl px-4 py-2 text-center text-sm font-semibold leading-snug focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 {{ $screen === $key ? 'bg-primary-600 text-white' : 'border border-slate-200 bg-white text-slate-800 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100' }}"
                    >{{ $label }}</button>
                @endforeach
            </div>
        </div>
        @if ($periodHint)
            <p class="max-w-3xl text-sm leading-relaxed text-slate-500">{{ $periodHint }}</p>
        @endif
        <div class="flex flex-wrap items-end gap-2">
            <div class="w-full sm:w-auto">
                <label class="text-xs font-semibold text-slate-500">Data da lista</label>
                <input type="date" wire:model.live="issue_date" class="mt-1 block min-h-11 w-full min-w-[11.5rem] rounded-xl border border-slate-200 px-3 py-2 text-sm sm:w-auto dark:border-slate-800 dark:bg-slate-950" />
            </div>
            @if ($report)
                <button type="button" x-data x-on:click="$dispatch('open-modal', 'baptism-regenerate')" class="inline-flex min-h-11 max-w-full items-center justify-center whitespace-normal rounded-xl bg-primary-600 px-4 py-2 text-center text-sm font-semibold leading-snug text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">Gerar / atualizar</button>
                <button type="button" x-data x-on:click="$dispatch('open-modal', 'baptism-new-revision')" class="inline-flex min-h-11 max-w-full items-center justify-center whitespace-normal rounded-xl border border-slate-200 px-4 py-2 text-center text-sm font-semibold leading-snug focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-slate-800">Nova revisão Rn</button>
                <button type="button" x-data x-on:click="$dispatch('open-modal', 'baptism-reset-periods')" class="inline-flex min-h-11 max-w-full items-center justify-center whitespace-normal rounded-xl border border-slate-200 px-4 py-2 text-center text-sm font-semibold leading-snug focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-slate-800">Restaurar períodos padrão</button>
            @else
                <button type="button" wire:click="generate" class="inline-flex min-h-11 max-w-full items-center justify-center whitespace-normal rounded-xl bg-primary-600 px-4 py-2 text-center text-sm font-semibold leading-snug text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">Gerar / atualizar</button>
                <button type="button" wire:click="generateNewRevision" class="inline-flex min-h-11 max-w-full items-center justify-center whitespace-normal rounded-xl border border-slate-200 px-4 py-2 text-center text-sm font-semibold leading-snug focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-slate-800">Nova revisão Rn</button>
                <button type="button" wire:click="resetSectionFilters" class="inline-flex min-h-11 max-w-full items-center justify-center whitespace-normal rounded-xl border border-slate-200 px-4 py-2 text-center text-sm font-semibold leading-snug focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-slate-800">Restaurar períodos padrão</button>
            @endif
            @if ($report)
                <a href="{{ route('secretaria.baptism-list.pdf', $report) }}" target="_blank" rel="noopener" class="inline-flex min-h-11 max-w-full items-center justify-center whitespace-normal rounded-xl border border-slate-200 px-4 py-2 text-center text-sm font-semibold leading-snug focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-slate-800">Baixar PDF</a>
                <a href="{{ route('secretaria.baptism-list.docx', $report) }}" class="inline-flex min-h-11 max-w-full items-center justify-center whitespace-normal rounded-xl border border-slate-200 px-4 py-2 text-center text-sm font-semibold leading-snug focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-slate-800">Baixar Word</a>
            @endif
        </div>
        @if ($report)
            @include('livewire.secretaria.partials.baptism-list-generate-confirm')
        @endif
    </div>

    @if (session('status'))
        <x-nexus.alert tone="success">{{ session('status') }}</x-nexus.alert>
    @endif

    @if ($pendingItems->isNotEmpty() && $screen !== 'versoes')
        <section aria-labelledby="pending-baptism-heading" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-900 dark:bg-amber-950/40">
            <h2 id="pending-baptism-heading" class="flex items-center gap-2 text-sm font-semibold text-amber-950 dark:text-amber-100">
                <x-nexus.glyph name="alert" class="h-4 w-4 shrink-0" />
                Para aprovar
            </h2>
            <p class="mt-1 text-sm text-amber-950 dark:text-amber-100">Estes itens entram na lista, mas o evento ainda não foi publicado.</p>
            <ul class="mt-3 space-y-1 text-sm text-amber-950 dark:text-amber-100">
                @foreach ($pendingItems as $item)
                    <li>{{ trim((($item->payload['date'] ?? '').' '.($item->payload['time'] ?? ''))) }} · {{ $item->payload['title'] ?? $item->payload['location_label'] ?? 'Item' }}</li>
                @endforeach
            </ul>
        </section>
    @endif

    @include('livewire.secretaria.partials.baptism-list-confirm')

    @if ($screen === 'versoes')
        @include('livewire.secretaria.partials.baptism-list-revisions')
    @elseif ($report && $screen === 'visualizacao')
        @include('livewire.secretaria.partials.baptism-list-preview')
    @elseif ($report)
        @php
            $regionalName = \App\Services\BaptismListReportBuilder::regionalDisplayName(
                $regional?->name ?? $report->regional?->name
            );
        @endphp
        <p class="text-sm text-slate-600 dark:text-slate-300">
            Emissão {{ $report->issue_date->format('d/m/Y') }} · R{{ sprintf('%02d', $report->revision) }} · {{ $hiddenCount }} item(ns) oculto(s)
            <button type="button" wire:click="hideBras" class="ml-2 text-primary-600 underline">Ocultar todos Brás</button>
        </p>

        @foreach ($displayBlocks as $block)
            @php
                $section = $block['key'];
                $items = $block['items'];
                $isRegionalMeetings = $block['type'] === 'regional_meetings';
                $sectionTitle = $isRegionalMeetings
                    ? ('Reuniões Regionais'.($regionalName ? ' - '.$regionalName : ''))
                    : ($sectionTitles[$section] ?? $section);
                $filter = $sectionFilters[$section] ?? null;
                $first = $items->first();
                $audienceIntro = $first?->payload['audience_intro']
                    ?? \App\Services\BaptismListReportBuilder::DEFAULT_AUDIENCE_INTROS[$section]
                    ?? null;
                $sectionHidden = $block['hidden'];
                $hideAction = $isRegionalMeetings ? 'hideRegionalMeetings' : "hideSection('{$section}')";
                $showAction = $isRegionalMeetings ? 'showRegionalMeetings' : "showSection('{$section}')";
            @endphp

            <section wire:key="baptism-section-{{ $section }}" class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800">
                <div class="border-b border-black/10 px-4 py-2 text-center dark:border-slate-700 {{ $sectionHidden ? 'bg-slate-100 dark:bg-slate-900' : 'bg-[#B3DDF2]' }}">
                    <h2 class="text-xs font-bold {{ $sectionHidden ? 'text-slate-600 dark:text-slate-300' : 'text-black' }}">{{ $sectionTitle }}</h2>
                </div>
                <div class="p-4">
                @if ($sectionHidden)
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm text-slate-600 dark:text-slate-300">
                            Secção oculta: não entra na visualização, no PDF nem no Word.
                            @if ($items->isNotEmpty())
                                {{ $items->count() }} {{ $items->count() === 1 ? 'item fica guardado' : 'itens ficam guardados' }}.
                            @endif
                            Eventos novos desta secção também entram ocultos ao atualizar a lista.
                        </p>
                        <button type="button" wire:click="{{ $showAction }}" class="inline-flex min-h-11 shrink-0 items-center justify-center whitespace-nowrap rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-slate-800 dark:bg-slate-900">Mostrar secção</button>
                    </div>
                @else
                <div class="mb-3 flex flex-col gap-3">
                    @if (in_array($section, $longSections, true))
                        <p class="text-xs leading-relaxed text-slate-500">Janela padrão longa (~2,5 / 3 meses)</p>
                    @elseif ($filter)
                        <p class="text-xs leading-relaxed text-slate-500">Janela padrão: até fim do mês seguinte ao início da lista</p>
                    @endif
                    <div class="flex flex-wrap items-end gap-2 text-sm">
                        @if ($filter)
                            <div class="w-full sm:w-auto">
                                <label class="block text-[10px] font-semibold uppercase text-slate-500">De</label>
                                <input type="date" wire:model="sectionFilters.{{ $section }}.from" class="mt-0.5 min-h-11 w-full min-w-[11.5rem] rounded-lg border border-slate-200 px-3 py-2 sm:w-auto dark:border-slate-800 dark:bg-slate-950" />
                            </div>
                            <div class="w-full sm:w-auto">
                                <label class="block text-[10px] font-semibold uppercase text-slate-500">Até</label>
                                <input type="date" wire:model="sectionFilters.{{ $section }}.to" class="mt-0.5 min-h-11 w-full min-w-[11.5rem] rounded-lg border border-slate-200 px-3 py-2 sm:w-auto dark:border-slate-800 dark:bg-slate-950" />
                            </div>
                            <button type="button" x-data x-on:click="$wire.periodSection = @js($section); $dispatch('open-modal', 'baptism-apply-period')" class="inline-flex min-h-11 max-w-full items-center justify-center whitespace-normal rounded-lg bg-slate-900 px-4 py-2 text-center font-semibold leading-snug text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:bg-slate-100 dark:text-slate-900">Aplicar período</button>
                        @endif
                        <button type="button" wire:click="{{ $hideAction }}" class="inline-flex min-h-11 max-w-full items-center justify-center whitespace-normal rounded-lg px-3 py-2 text-center font-semibold leading-snug underline underline-offset-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">Ocultar secção</button>
                        @if ($items->contains(fn ($item) => ! $item->is_visible))
                            <button type="button" wire:click="{{ $showAction }}" class="inline-flex min-h-11 max-w-full items-center justify-center whitespace-normal rounded-lg px-3 py-2 text-center font-semibold leading-snug underline underline-offset-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">Mostrar todos</button>
                        @endif
                    </div>
                </div>

                @if (in_array($section, $introListSections, true) && $audienceIntro)
                    <p class="mb-2 text-xs leading-snug text-slate-600 dark:text-slate-300">{{ $audienceIntro }}</p>
                @endif

                @if ($items->isEmpty())
                    <p class="text-sm text-slate-500">Nenhum item neste período. Amplie as datas e clique em Aplicar período.</p>
                @elseif ($isRegionalMeetings)
                    @php
                        $byType = $items->groupBy('section_key');
                        $listItems = $byType->get('reunioes');
                    @endphp
                    <div class="space-y-3">
                        @if ($listItems && $listItems->isNotEmpty())
                            @foreach ($listItems as $item)
                                @php $p = $item->payload ?: []; @endphp
                                <div>
                                    <h3 class="border border-black/20 bg-[#B3DDF2] px-2 py-0.5 text-center text-xs font-bold text-black">{{ $p['title'] ?? 'Reunião' }}</h3>
                                    <ul class="divide-y divide-slate-200 border border-t-0 border-slate-300 bg-white dark:divide-slate-800 dark:border-slate-700 dark:bg-slate-950">
                                        <li class="flex items-start justify-between gap-3 px-2 py-1 {{ $item->is_visible ? '' : 'opacity-50' }}">
                                            <div class="min-w-0 text-xs leading-snug text-slate-800 dark:text-slate-100 [&_b]:font-bold [&_em]:italic [&_i]:italic [&_ol]:list-decimal [&_ol]:pl-4 [&_strong]:font-bold [&_u]:underline [&_ul]:my-0.5 [&_ul]:list-disc [&_ul]:pl-4">
                                                @if (($p['status'] ?? null) === 'pending_approval')
                                                    <span class="mb-1 inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-950">Para aprovar</span>
                                                @endif
                                                {!! \App\Support\SimpleRichText::toHtml($p['description'] ?? '') !!}
                                            </div>
                                            <button type="button" wire:click="toggleItem({{ $item->id }})" class="inline-flex min-h-11 shrink-0 items-center justify-center whitespace-nowrap rounded-lg border border-slate-200 bg-white px-4 text-xs font-semibold focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-slate-800 dark:bg-slate-900">
                                                {{ $item->is_visible ? 'Ocultar' : 'Mostrar' }}
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            @endforeach
                        @endif
                        @foreach (\App\Services\BaptismListReportBuilder::NARRATIVE_MEETING_SECTIONS as $typeKey)
                            @php $typeItems = $byType->get($typeKey); @endphp
                            @continue(! $typeItems || $typeItems->isEmpty())
                            @php
                                $typeTitle = $sectionTitles[$typeKey] ?? $typeKey;
                                if ($typeKey === 'obra-piedade' && $regionalName) {
                                    $typeTitle = 'Reunião de Ensinamentos da Obra da Piedade na Regional '.$regionalName;
                                }
                            @endphp
                            <div>
                                <h3 class="border border-black/20 bg-[#B3DDF2] px-2 py-0.5 text-center text-xs font-bold text-black">{{ $typeTitle }}</h3>
                                <ul class="divide-y divide-slate-200 border border-t-0 border-slate-300 bg-white dark:divide-slate-800 dark:border-slate-700 dark:bg-slate-950">
                                    @foreach ($typeItems as $item)
                                        @php $p = $item->payload ?? []; @endphp
                                        <li class="flex items-start justify-between gap-3 px-2 py-1 {{ $item->is_visible ? '' : 'opacity-50' }}">
                                            <div class="min-w-0 text-xs leading-snug text-slate-800 dark:text-slate-100 [&_b]:font-bold [&_em]:italic [&_i]:italic [&_ol]:list-decimal [&_ol]:pl-4 [&_strong]:font-bold [&_u]:underline [&_ul]:my-0.5 [&_ul]:list-disc [&_ul]:pl-4">
                                                @if (($p['status'] ?? null) === 'pending_approval')
                                                    <span class="mb-1 inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-950">Para aprovar</span>
                                                @endif
                                                {!! \App\Support\SimpleRichText::toHtml($p['description'] ?? $p['title'] ?? '') !!}
                                            </div>
                                            <button type="button" wire:click="toggleItem({{ $item->id }})" class="inline-flex min-h-11 shrink-0 items-center justify-center whitespace-nowrap rounded-lg border border-slate-200 bg-white px-4 text-xs font-semibold focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-slate-800 dark:bg-slate-900">
                                                {{ $item->is_visible ? 'Ocultar' : 'Mostrar' }}
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                @else
                    @php
                        $firstMonthNum = null;
                        $firstDate = $items->first()?->payload['date'] ?? null;
                        if (is_string($firstDate) && preg_match('#^\d{1,2}/(\d{1,2})#', $firstDate, $fm)) {
                            $firstMonthNum = (int) $fm[1];
                        }
                    @endphp
                    <ul class="overflow-hidden rounded-lg border border-slate-300 dark:border-slate-700">
                        @foreach ($items as $item)
                            @php
                                $p = $item->payload ?? [];
                                $rowMonth = null;
                                if (!empty($p['date']) && preg_match('#^\d{1,2}/(\d{1,2})#', (string) $p['date'], $rm)) {
                                    $rowMonth = (int) $rm[1];
                                }
                                $altMonth = $firstMonthNum && $rowMonth && $rowMonth !== $firstMonthNum;
                            @endphp
                            <li class="flex items-start justify-between gap-3 border-b border-slate-300 px-2 py-1 last:border-b-0 dark:border-slate-700 {{ $altMonth ? 'bg-[#D9D9D9] dark:bg-slate-800' : 'bg-white dark:bg-slate-950' }} {{ $item->is_visible ? '' : 'opacity-50' }}">
                                <div class="min-w-0 text-xs leading-snug">
                                    @if (($p['status'] ?? null) === 'pending_approval')
                                        <span class="mb-1 inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-950">Para aprovar</span>
                                    @endif
                                    @if (in_array($section, $introListSections, true))
                                        <span class="font-medium">{{ $p['date'] ?? '' }} {{ $p['weekday'] ?? '' }} {{ $p['time'] ?? '' }}</span>
                                        · {{ \App\Services\BaptismListReportBuilder::withoutRepeatedCity($p['location_label'] ?? $p['title'] ?? '') }}
                                        @include('reports.baptism-list.libras-icon', ['show' => ! empty($p['has_libras'])])
                                    @elseif (!empty($p['date']))
                                        <span class="font-medium">{{ $p['date'] }} {{ $p['weekday'] ?? '' }} {{ $p['time'] ?? '' }}</span>
                                        · {{ \App\Services\BaptismListReportBuilder::withoutRepeatedCity($p['location_label'] ?? $p['title'] ?? '') }}
                                        @include('reports.baptism-list.libras-icon', ['show' => ! empty($p['has_libras'])])
                                        · {{ $p['attendant'] ?? '' }}
                                    @else
                                        <span class="font-medium">{{ $p['title'] ?? '' }}</span>
                                        <span class="block text-slate-500">{{ \Illuminate\Support\Str::limit(strip_tags($p['body'] ?? $p['description'] ?? ''), 160) }}</span>
                                    @endif
                                </div>
                                <button type="button" wire:click="toggleItem({{ $item->id }})" class="inline-flex min-h-11 shrink-0 items-center justify-center whitespace-nowrap rounded-lg border border-slate-200 bg-white px-4 text-xs font-semibold focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-slate-800 dark:bg-slate-900">
                                    {{ $item->is_visible ? 'Ocultar' : 'Mostrar' }}
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif
                @endif
                </div>
            </section>
        @endforeach
    @else
        <x-nexus.empty-state title="Nenhuma lista gerada" description="Escolha a data e clique em Gerar / atualizar." />
    @endif
</div>
