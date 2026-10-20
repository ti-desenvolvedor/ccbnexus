@php
    $visible = $report->items->where('is_visible', true);
    $titles = \App\Services\BaptismListReportBuilder::SECTION_TITLES;
    $regionalName = \App\Services\BaptismListReportBuilder::regionalDisplayName(
        $regional?->name ?? $report->regional?->name
    );
@endphp
<section aria-labelledby="preview-heading" class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-950">
    <h2 id="preview-heading" class="text-center text-sm font-semibold text-slate-900 dark:text-slate-100">Visualização da lista</h2>
    <p class="mt-1 text-center text-sm text-slate-600 dark:text-slate-300">
        R{{ sprintf('%02d', $report->revision) }} · só o que está visível. O PDF usa este mesmo recorte.
    </p>
    @if ($visible->isEmpty())
        <div class="mt-4">
            <x-nexus.empty-state title="Nada visível" description="Volte à listagem e mostre os itens que devem sair no boletim." />
        </div>
    @else
        <div class="mt-4 space-y-4">
            @foreach ($visible->groupBy('section_key') as $section => $items)
                <div>
                    @php
                        $sectionTitle = $section === 'reunioes'
                            ? ('Reuniões Regionais'.($regionalName ? ' - '.$regionalName : ''))
                            : ($titles[$section] ?? $section);
                    @endphp
                    <h3 class="bg-[#B3DDF2] px-2 py-1 text-center text-xs font-bold text-black">{{ $sectionTitle }}</h3>
                    <ul class="divide-y divide-slate-300 border border-t-0 border-slate-300">
                        @foreach ($items as $item)
                            @php
                                $p = $item->payload ?: [];
                            @endphp
                            <li class="px-2 py-1 text-xs leading-snug text-slate-900">
                                @if (($p['status'] ?? null) === 'pending_approval')
                                    <span class="mr-1 font-semibold text-amber-800">Para aprovar</span>
                                @endif
                                @php
                                    $isNarrative = in_array($section, \App\Services\BaptismListReportBuilder::NARRATIVE_MEETING_SECTIONS, true);
                                @endphp
                                @if ($section === 'reunioes')
                                    <span class="font-medium">{{ $p['title'] ?? '' }}</span>
                                    @if (! empty($p['description']))
                                        <span class="mt-0.5 block [&_b]:font-bold [&_strong]:font-bold">{!! \App\Support\SimpleRichText::toHtml($p['description']) !!}</span>
                                    @endif
                                @elseif ($isNarrative)
                                    <span class="[&_b]:font-bold [&_strong]:font-bold">{!! \App\Support\SimpleRichText::toHtml($p['description'] ?? $p['title'] ?? '') !!}</span>
                                @elseif (! empty($p['date']))
                                    <span class="font-medium">{{ $p['date'] }} {{ $p['weekday'] ?? '' }} {{ $p['time'] ?? '' }}</span>
                                    · {{ \App\Services\BaptismListReportBuilder::withoutRepeatedCity($p['location_label'] ?? $p['title'] ?? '') }}
                                    @include('reports.baptism-list.libras-icon', ['show' => ! empty($p['has_libras'])])
                                @else
                                    <span class="font-medium">{{ $p['title'] ?? '' }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    @endif
</section>
