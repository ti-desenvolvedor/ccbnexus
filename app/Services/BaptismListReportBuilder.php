<?php

namespace App\Services;

use App\Models\BaptismListReport;
use App\Models\BaptismListReportItem;
use App\Models\Event;
use App\Models\EventTypeAudiencePreset;
use App\Models\RegionalNotice;
use App\Models\RegionalScheduleChange;
use App\Models\RegionalSpecialCollection;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BaptismListReportBuilder
{
    /** @var array<string, int> */
    public const SECTION_ORDER = [
        'batismos' => 10,
        'mocidade' => 20,
        'jovens12' => 30,
        'ensaios' => 40,
        'reunioes' => 45,
        'organistas' => 50,
        'obra-piedade' => 60,
        'ra' => 70,
        'rrm' => 80,
        'rga' => 90,
        'rco' => 100,
        'rma' => 110,
        'santa-ceia' => 120,
        'coletas-especiais' => 130,
        'alteracoes' => 140,
        'avisos' => 150,
    ];

    /** @var array<string, string> */
    public const SECTION_TITLES = [
        'batismos' => 'Batismos',
        'mocidade' => 'Reuniões da Mocidade',
        'jovens12' => 'Cultos para Jovens Acima de 12 Anos',
        'ensaios' => 'Ensaios Regionais',
        'reunioes' => 'Reuniões',
        'organistas' => 'Reunião Técnica para Organistas',
        'obra-piedade' => 'Reunião de Ensinamentos da Obra da Piedade',
        'ra' => 'Reunião das Administrações (RA)',
        'rrm' => 'Reunião Regional Ministerial (RRM)',
        'rga' => 'Reunião Geral da Administração',
        'rco' => 'Reunião de Conscientização e Orientação (RCO)',
        'rma' => 'Reunião do Ministério e Administração - RMA',
        'santa-ceia' => 'Santa Ceias',
        'coletas-especiais' => 'Coletas especiais',
        'alteracoes' => 'Alterações',
        'avisos' => 'Avisos',
    ];

    /** Secções só com tabela data/hora/local/atende (boletim clássico). */
    public const LIST_SECTIONS = [
        'batismos', 'mocidade', 'jovens12', 'ensaios', 'santa-ceia',
    ];

    /**
     * Lista data/hora/local dentro do bloco “Reuniões Regionais”
     * (mesmo filtro de período das reuniões narrativas).
     */
    public const REGIONAL_LIST_SECTIONS = [
        'reunioes',
    ];

    /**
     * Reuniões regionais em parágrafo narrativo (como no PDF modelo):
     * título + texto “No dia … haverá essa reunião…”, sem tabela de agenda.
     */
    public const NARRATIVE_MEETING_SECTIONS = [
        'organistas', 'obra-piedade', 'ra', 'rrm', 'rga',
    ];

    /**
     * Reuniões com intro de público + tabela data/hora/local (RCO / RMA no modelo).
     */
    public const INTRO_LIST_MEETING_SECTIONS = [
        'rco', 'rma',
    ];

    /** @deprecated use NARRATIVE_MEETING_SECTIONS + INTRO_LIST_MEETING_SECTIONS */
    public const MEETING_SECTIONS = [
        'organistas', 'obra-piedade', 'ra', 'rrm', 'rga', 'rco', 'rma',
    ];

    /** Secções só texto (sem linha de agenda). */
    public const TEXT_SECTIONS = [
        'coletas-especiais', 'alteracoes', 'avisos',
    ];

    /** Textos de público-alvo padrão (PDF modelo REA). */
    public const DEFAULT_AUDIENCE_INTROS = [
        'rco' => 'Para os irmãos Anciães, Diáconos, Cooperadores do Ofício Ministerial, Cooperadores de Jovens e Menores, Colaboradores da Administração, Porteiros e Auxiliares da Porta, irmãos e irmãs do Fundo Bíblico, Encarregados de Orquestra Regionais e Locais, Instrutores e Instrutoras da Música, Examinadoras, irmãs da Obra da Piedade, Colaboradoras do Espaço Bíblico Infantil, Auxiliares de Jovens e Menores, Intérpretes e candidatos(as) de LIBRAS dos seus respectivos municípios.',
        'rma' => 'Para os irmãos Anciães, Diáconos, Cooperadores do Ofício Ministerial, Cooperadores de Jovens e Menores, Administradores Estatutários e Conselho Fiscal e Colaboradores.',
    ];

    /**
     * Janela longa (~2,5 / 3 meses): data da lista → fim do mês +2.
     * Ex.: 15/10 → até 31/12.
     */
    public const LONG_PERIOD_SECTIONS = [
        'batismos', 'mocidade', 'jovens12', 'santa-ceia',
    ];

    /**
     * Filtro único na UI para organistas / obra / RA / RRM / RGA
     * (no PDF continuam com títulos separados sob “Reuniões Regionais”).
     */
    public const REGIONAL_MEETINGS_PERIOD_KEY = 'reunioes-regionais';

    /**
     * Fim do período global da lista (igual à janela longa).
     */
    public function periodEndForIssue(Carbon $issueDate): Carbon
    {
        return $issueDate->copy()->startOfMonth()->addMonths(2)->endOfMonth()->startOfDay();
    }

    /**
     * Fim padrão por secção.
     * - Batismos / Mocidade / Jovens: janela longa (~2,5 meses).
     * - Demais: fim do mês da data de início + mês seguinte (ex.: 15/10 → 30/11).
     */
    public function defaultPeriodEndForSection(string $section, Carbon $issueDate): Carbon
    {
        if (in_array($section, self::LONG_PERIOD_SECTIONS, true)) {
            return $this->periodEndForIssue($issueDate);
        }

        return $issueDate->copy()->startOfMonth()->addMonths(1)->endOfMonth()->startOfDay();
    }

    /**
     * @return array<string, array{from: string, to: string}>
     */
    public function defaultSectionPeriods(Carbon $issueDate): array
    {
        $issue = $issueDate->copy()->startOfDay();
        $from = $issue->toDateString();
        $periods = [];

        foreach (array_keys(self::SECTION_ORDER) as $section) {
            if (in_array($section, self::TEXT_SECTIONS, true)) {
                continue;
            }
            // Um só filtro para o bloco “Reuniões Regionais”.
            if (in_array($section, self::regionalBlockSectionKeys(), true)) {
                continue;
            }
            $periods[$section] = [
                'from' => $from,
                'to' => $this->defaultPeriodEndForSection($section, $issue)->toDateString(),
            ];
        }

        $periods[self::REGIONAL_MEETINGS_PERIOD_KEY] = [
            'from' => $from,
            'to' => $this->defaultPeriodEndForSection(self::REGIONAL_MEETINGS_PERIOD_KEY, $issue)->toDateString(),
        ];

        return $periods;
    }

    public static function locationLabel(?string $name, ?string $city = null): string
    {
        $name = trim((string) $name);
        $city = trim((string) $city);

        if ($city !== '' && ($name === '' || ! self::textContainsCity($name, $city))) {
            $name = $name === '' ? $city : $name.' – '.$city;
        }

        return self::withoutRepeatedCity($name);
    }

    public static function withoutRepeatedCity(?string $label): string
    {
        $label = trim((string) $label);
        $separator = ' – ';
        $splitAt = mb_strrpos($label, $separator);
        if ($splitAt === false) {
            return $label;
        }

        $head = trim(mb_substr($label, 0, $splitAt));
        $tail = trim(mb_substr($label, $splitAt + mb_strlen($separator)));
        if ($head !== '' && $tail !== '' && self::textContainsCity($head, $tail)) {
            return $head;
        }

        return $label;
    }

    /**
     * @return list<string>
     */
    public static function locationLines(?string $label): array
    {
        $label = self::withoutRepeatedCity($label);
        if ($label === '') {
            return [];
        }

        if (mb_strlen($label) > 18 && preg_match('/^(.*?)\s+[–—-]\s+(.*)$/u', $label, $parts)) {
            return [trim($parts[1]), trim($parts[2])];
        }

        return [$label];
    }

    public static function pdfLocationHtml(?string $label): string
    {
        $lines = self::locationLines($label);
        if ($lines === []) {
            return '';
        }

        $keep = static function (string $part): string {
            return str_replace('-', '&#8209;', e($part));
        };

        return implode('<br>', array_map($keep, $lines));
    }

    protected static function textContainsCity(string $text, string $city): bool
    {
        $normalize = static function (string $value): string {
            $value = mb_strtolower($value);
            $value = str_replace(['–', '—', '-'], ' ', $value);
            $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

            return trim($value);
        };

        $city = $normalize($city);
        if ($city === '') {
            return false;
        }

        return str_contains($normalize($text), $city);
    }

    /**
     * Secções do bloco “Reuniões Regionais” (lista + parágrafos).
     *
     * @return list<string>
     */
    public static function regionalBlockSectionKeys(): array
    {
        return array_merge(self::REGIONAL_LIST_SECTIONS, self::NARRATIVE_MEETING_SECTIONS);
    }

    /**
     * Nome da regional sem o prefixo “Regional”.
     */
    public static function regionalDisplayName(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '';
        }
        if (preg_match('/^Regional\s+/iu', $name)) {
            return trim(preg_replace('/^Regional\s+/iu', '', $name) ?? $name);
        }

        return $name;
    }

    /**
     * @param  array<string, array{from?: string, to?: string}>|null  $sectionPeriods
     */
    public function generateOrSync(
        int $regionalId,
        Carbon $issueDate,
        User $user,
        bool $bumpRevision = false,
        ?array $sectionPeriods = null,
    ): BaptismListReport {
        return DB::transaction(function () use ($regionalId, $issueDate, $user, $bumpRevision, $sectionPeriods) {
            $issue = $issueDate->copy()->startOfDay();
            $periodStart = $issue->copy();
            $periodEnd = $this->periodEndForIssue($issue);
            $defaults = $this->defaultSectionPeriods($issue);

            $existing = BaptismListReport::query()
                ->where('regional_id', $regionalId)
                ->whereDate('issue_date', $issue)
                ->orderByDesc('revision')
                ->first();

            $revision = 0;
            $hiddenSections = BaptismListSectionVisibility::hiddenSections($existing);
            if ($existing && $bumpRevision) {
                $revision = (int) $existing->revision + 1;
                $resolvedPeriods = $this->normalizeSectionPeriods($sectionPeriods ?? $defaults, $defaults);
                $visibilityMap = $this->visibilityMap($existing);
                $report = BaptismListReport::query()->create([
                    'regional_id' => $regionalId,
                    'issue_date' => $issue,
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                    'revision' => $revision,
                    'edition_at' => now(),
                    'status' => BaptismListReport::STATUS_DRAFT,
                    'generated_by' => $user->id,
                    'generated_at' => now(),
                    'meta' => ['section_periods' => $resolvedPeriods, 'hidden_sections' => $hiddenSections],
                ]);
            } elseif ($existing) {
                $report = $existing;
                $visibilityMap = $this->visibilityMap($report);
                $preserved = is_array($report->meta['section_periods'] ?? null)
                    ? $report->meta['section_periods']
                    : [];
                $resolvedPeriods = $this->normalizeSectionPeriods(
                    $sectionPeriods ?? $preserved ?: $defaults,
                    $defaults
                );
                $report->items()->delete();
                $report->checks()->delete();
                $meta = array_merge($report->meta ?? [], ['section_periods' => $resolvedPeriods]);
                unset($meta['confirmation'], $meta['publication'], $meta['rejection']);
                $nextStatus = $report->status === BaptismListReport::STATUS_ARCHIVED
                    ? BaptismListReport::STATUS_ARCHIVED
                    : BaptismListReport::STATUS_DRAFT;
                $report->update([
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                    'edition_at' => now(),
                    'status' => $nextStatus,
                    'generated_by' => $user->id,
                    'generated_at' => now(),
                    'meta' => $meta,
                ]);
            } else {
                $resolvedPeriods = $this->normalizeSectionPeriods($sectionPeriods ?? $defaults, $defaults);
                $report = BaptismListReport::query()->create([
                    'regional_id' => $regionalId,
                    'issue_date' => $issue,
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                    'revision' => 0,
                    'edition_at' => now(),
                    'status' => BaptismListReport::STATUS_DRAFT,
                    'generated_by' => $user->id,
                    'generated_at' => now(),
                    'meta' => ['section_periods' => $resolvedPeriods],
                ]);
                $visibilityMap = [];
            }

            $sort = 0;
            foreach ($this->collectEventItems($regionalId, $periodStart, $periodEnd, $resolvedPeriods) as $item) {
                $key = $item['source_type'].':'.($item['source_id'] ?? '0').':'.$item['section_key'];
                BaptismListReportItem::query()->create([
                    'baptism_list_report_id' => $report->id,
                    'section_key' => $item['section_key'],
                    'sort_order' => ++$sort,
                    'is_visible' => $visibilityMap[$key] ?? ! in_array($item['section_key'], $hiddenSections, true),
                    'source_type' => $item['source_type'],
                    'source_id' => $item['source_id'],
                    'payload' => $item['payload'],
                ]);
            }

            foreach ($this->collectCollections($regionalId, $issue) as $item) {
                $key = $item['source_type'].':'.($item['source_id'] ?? '0').':'.$item['section_key'];
                BaptismListReportItem::query()->create([
                    'baptism_list_report_id' => $report->id,
                    'section_key' => $item['section_key'],
                    'sort_order' => ++$sort,
                    'is_visible' => $visibilityMap[$key] ?? ! in_array($item['section_key'], $hiddenSections, true),
                    'source_type' => $item['source_type'],
                    'source_id' => $item['source_id'],
                    'payload' => $item['payload'],
                ]);
            }

            foreach ($this->collectChanges($regionalId, $issue) as $item) {
                $key = $item['source_type'].':'.($item['source_id'] ?? '0').':'.$item['section_key'];
                BaptismListReportItem::query()->create([
                    'baptism_list_report_id' => $report->id,
                    'section_key' => $item['section_key'],
                    'sort_order' => ++$sort,
                    'is_visible' => $visibilityMap[$key] ?? ! in_array($item['section_key'], $hiddenSections, true),
                    'source_type' => $item['source_type'],
                    'source_id' => $item['source_id'],
                    'payload' => $item['payload'],
                ]);
            }

            foreach ($this->collectNotices($regionalId, $issue) as $item) {
                $key = $item['source_type'].':'.($item['source_id'] ?? '0').':'.$item['section_key'];
                BaptismListReportItem::query()->create([
                    'baptism_list_report_id' => $report->id,
                    'section_key' => $item['section_key'],
                    'sort_order' => ++$sort,
                    'is_visible' => $visibilityMap[$key] ?? ! in_array($item['section_key'], $hiddenSections, true),
                    'source_type' => $item['source_type'],
                    'source_id' => $item['source_id'],
                    'payload' => $item['payload'],
                ]);
            }

            return $report->fresh(['items', 'regional']);
        });
    }

    /**
     * @return array<string, bool>
     */
    protected function visibilityMap(BaptismListReport $report): array
    {
        return $report->items()
            ->get()
            ->mapWithKeys(fn (BaptismListReportItem $item) => [
                $item->source_type.':'.($item->source_id ?? '0').':'.$item->section_key => $item->is_visible,
            ])
            ->all();
    }

    /**
     * @param  array<string, array{from: string, to: string}>  $sectionPeriods
     * @return list<array{section_key: string, source_type: string, source_id: int|null, payload: array}>
     */
    protected function collectEventItems(int $regionalId, Carbon $from, Carbon $to, array $sectionPeriods = []): array
    {
        $presets = app(EventTypeAudiencePresetService::class);

        $events = Event::query()
            ->with(['eventType', 'location', 'regional', 'parent', 'roleAssignments.roleTemplate', 'roleAssignments.user'])
            ->where('regional_id', $regionalId)
            ->where('include_on_baptism_list', true)
            ->whereNotIn('status', ['cancelled', 'rejected', 'draft'])
            // Pais = 1.ª data da série; filhos = datas seguintes (planners são estritamente posteriores).
            ->whereBetween('starts_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->orderBy('starts_at')
            ->get();

        $items = [];
        foreach ($events as $event) {
            $slug = $event->eventType?->slug;
            $section = $event->eventType?->meta['report_section']
                ?? match ($slug) {
                    'batismo' => 'batismos',
                    'reuniao-mocidade', 'mocidade-online' => 'mocidade',
                    'culto-jovens-12' => 'jovens12',
                    'ensaio-regional' => 'ensaios',
                    'santa-ceia' => 'santa-ceia',
                    'rco' => 'rco',
                    'rma' => 'rma',
                    'ra' => 'ra',
                    'rrm' => 'rrm',
                    'rga' => 'rga',
                    'reuniao' => 'reunioes',
                    'reuniao-organistas' => 'organistas',
                    'reuniao-obra-piedade' => 'obra-piedade',
                    default => null,
                };
            if (! $section || ! isset(self::SECTION_ORDER[$section])) {
                $section = 'reunioes';
            }

            if (! $this->eventInSectionPeriod($event, $section, $sectionPeriods, $from, $to)) {
                continue;
            }

            $locationLabel = $event->location
                ? self::locationLabel($event->location->name, $event->location->city)
                : ($event->title ?? '—');

            $preset = $presets->presetForType($event->event_type_id);
            $layout = match (true) {
                in_array($section, self::NARRATIVE_MEETING_SECTIONS, true) => 'narrative',
                in_array($section, self::INTRO_LIST_MEETING_SECTIONS, true) => 'intro_list',
                default => $preset?->report_layout ?? 'list_only',
            };

            $audienceIntro = $event->eventType?->meta['report_audience_intro']
                ?? self::DEFAULT_AUDIENCE_INTROS[$section]
                ?? null;

            $items[] = [
                'section_key' => $section,
                'source_type' => 'event',
                'source_id' => $event->id,
                'payload' => [
                    'title' => $event->title,
                    'starts_at' => $event->starts_at?->toIso8601String(),
                    'date' => $event->starts_at?->format('d/m'),
                    'weekday' => $event->starts_at?->locale('pt_BR')->isoFormat('ddd'),
                    'time' => $event->starts_at?->format('H:i'),
                    'location_label' => $locationLabel,
                    'attendant' => $event->attendantLabel() ?: '— — — — — —',
                    'has_libras' => (bool) $event->has_libras,
                    'description' => $this->resolveEventDescription($event, $preset, $presets, $section),
                    'description_from_template' => in_array($section, self::NARRATIVE_MEETING_SECTIONS, true)
                        && trim((string) ($preset?->invitation_paragraph ?? '')) !== '',
                    'audience_intro' => $audienceIntro,
                    'month_label' => $event->starts_at?->locale('pt_BR')->isoFormat('MMMM'),
                    'type_slug' => $slug,
                    'report_layout' => $layout,
                    'status' => $event->effectiveStatus(),
                ],
            ];
        }

        usort($items, function ($a, $b) {
            $oa = self::SECTION_ORDER[$a['section_key']] ?? 999;
            $ob = self::SECTION_ORDER[$b['section_key']] ?? 999;
            if ($oa !== $ob) {
                return $oa <=> $ob;
            }

            return strcmp($a['payload']['starts_at'] ?? '', $b['payload']['starts_at'] ?? '');
        });

        return $items;
    }

    /**
     * @return list<array{section_key: string, source_type: string, source_id: int|null, payload: array}>
     */
    protected function collectNotices(int $regionalId, Carbon $issue): array
    {
        return RegionalNotice::query()
            ->where('regional_id', $regionalId)
            ->where('is_active', true)
            ->where(function ($q) use ($issue) {
                // Permanentes (Halloween, Eleições, etc.) + avisos com vigência na data da lista.
                $q->where('is_standing', true)
                    ->orWhere(function ($q2) use ($issue) {
                        $q2->whereDate('valid_from', '<=', $issue)
                            ->whereDate('valid_until', '>=', $issue);
                    });
            })
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get()
            ->map(fn (RegionalNotice $n) => [
                'section_key' => 'avisos',
                'source_type' => 'notice',
                'source_id' => $n->id,
                'payload' => [
                    'title' => $n->title,
                    'body' => $n->body,
                    'category' => $n->category,
                    'is_standing' => (bool) $n->is_standing,
                ],
            ])
            ->all();
    }

    /**
     * @return list<array{section_key: string, source_type: string, source_id: int|null, payload: array}>
     */
    protected function collectChanges(int $regionalId, Carbon $issue): array
    {
        return RegionalScheduleChange::query()
            ->where('regional_id', $regionalId)
            ->where('is_active', true)
            ->whereDate('valid_from', '<=', $issue)
            ->whereDate('valid_until', '>=', $issue)
            ->orderBy('title')
            ->get()
            ->map(fn (RegionalScheduleChange $c) => [
                'section_key' => 'alteracoes',
                'source_type' => 'schedule_change',
                'source_id' => $c->id,
                'payload' => [
                    'title' => $c->title,
                    'body' => $c->body,
                    'change_type' => $c->change_type,
                ],
            ])
            ->all();
    }

    /**
     * @return list<array{section_key: string, source_type: string, source_id: int|null, payload: array}>
     */
    protected function collectCollections(int $regionalId, Carbon $issue): array
    {
        return RegionalSpecialCollection::query()
            ->where('regional_id', $regionalId)
            ->where('is_active', true)
            ->whereDate('valid_from', '<=', $issue)
            ->whereDate('valid_until', '>=', $issue)
            ->orderBy('mode')
            ->orderBy('title')
            ->get()
            ->map(fn (RegionalSpecialCollection $c) => [
                'section_key' => 'coletas-especiais',
                'source_type' => 'special_collection',
                'source_id' => $c->id,
                'payload' => [
                    'title' => $c->title,
                    'body' => $c->body,
                    'mode' => $c->mode,
                    'collection_on' => $c->collection_on?->format('d/m/Y'),
                ],
            ])
            ->all();
    }

    protected function resolveEventDescription(
        Event $event,
        ?EventTypeAudiencePreset $preset,
        EventTypeAudiencePresetService $presets,
        string $section = '',
    ): string {
        // RCO/RMA: o parágrafo de público fica em audience_intro.
        if (in_array($section, self::INTRO_LIST_MEETING_SECTIONS, true)) {
            return '';
        }

        // Reuniões narrativas: o texto é sempre o template do tipo,
        // com data, hora e local preenchidos a partir do evento.
        if (in_array($section, self::NARRATIVE_MEETING_SECTIONS, true) && $preset) {
            $rendered = trim($presets->renderParagraph($preset, $event));
            if ($rendered !== '') {
                return $rendered;
            }
        }

        $raw = trim((string) ($event->description ?? ''));
        if ($raw !== '' && $presets->containsPlaceholder($raw)) {
            return trim($presets->renderTemplate($raw, $event));
        }
        if ($raw !== '' && ! str_starts_with($raw, 'Seed Agenda') && ! str_starts_with($raw, 'No dia ')) {
            return $raw;
        }

        if ($preset) {
            return trim($presets->renderParagraph($preset, $event));
        }

        return '';
    }

    /**
     * @param  array<string, array{from?: string, to?: string}>  $input
     * @param  array<string, array{from: string, to: string}>  $defaults
     * @return array<string, array{from: string, to: string}>
     */
    protected function normalizeSectionPeriods(array $input, array $defaults): array
    {
        $out = $defaults;
        foreach ($input as $section => $range) {
            if (! isset($defaults[$section]) || ! is_array($range)) {
                continue;
            }
            $from = $range['from'] ?? $defaults[$section]['from'];
            $to = $range['to'] ?? $defaults[$section]['to'];
            try {
                $fromC = Carbon::parse($from)->startOfDay();
                $toC = Carbon::parse($to)->startOfDay();
            } catch (\Throwable) {
                continue;
            }
            if ($toC->lt($fromC)) {
                $toC = $fromC->copy();
            }
            $out[$section] = [
                'from' => $fromC->toDateString(),
                'to' => $toC->toDateString(),
            ];
        }

        return $out;
    }

    /**
     * @param  array<string, array{from: string, to: string}>  $sectionPeriods
     */
    protected function eventInSectionPeriod(
        Event $event,
        string $section,
        array $sectionPeriods,
        Carbon $fallbackFrom,
        Carbon $fallbackTo,
    ): bool {
        if (! $event->starts_at) {
            return false;
        }

        $periodKey = in_array($section, self::regionalBlockSectionKeys(), true)
            ? self::REGIONAL_MEETINGS_PERIOD_KEY
            : $section;

        $from = isset($sectionPeriods[$periodKey]['from'])
            ? Carbon::parse($sectionPeriods[$periodKey]['from'])->startOfDay()
            : $fallbackFrom->copy()->startOfDay();
        $to = isset($sectionPeriods[$periodKey]['to'])
            ? Carbon::parse($sectionPeriods[$periodKey]['to'])->endOfDay()
            : $fallbackTo->copy()->endOfDay();

        return $event->starts_at->betweenIncluded($from, $to);
    }
}
