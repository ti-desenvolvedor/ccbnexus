<?php

namespace App\Services\Agenda;

use App\Models\Event;

/**
 * Resumo da agenda anual: séries da Visão Geral, cada evento uma vez, com os meses.
 * A classificação (série, título exibido e meses) é a mesma no PDF do seed e no caderno.
 */
class AgendaSeriesSummary
{
    /** @var array<int, string> */
    public const MONTHS = [
        1 => 'Jan',
        2 => 'Fev',
        3 => 'Mar',
        4 => 'Abr',
        5 => 'Mai',
        6 => 'Jun',
        7 => 'Jul',
        8 => 'Ago',
        9 => 'Set',
        10 => 'Out',
        11 => 'Nov',
        12 => 'Dez',
    ];

    /**
     * Séries da planilha Visão Geral e das secções do boletim, na ordem de leitura.
     *
     * @var array<string, array{title: string, order: int}>
     */
    private const SERIES = [
        'ago' => ['title' => 'Assembleias Gerais Ordinárias', 'order' => 10],
        'batismos' => ['title' => 'Batismos', 'order' => 20],
        'mocidade' => ['title' => 'Reuniões da Mocidade', 'order' => 30],
        'jovens' => ['title' => 'Culto para Jovens', 'order' => 40],
        'musical' => ['title' => 'Administração Musical', 'order' => 50],
        'ensaios' => ['title' => 'Ensaios Regionais', 'order' => 60],
        'piedade-atendimento' => ['title' => 'Reuniões de Atendimento da Obra da Piedade', 'order' => 70],
        'piedade-ensinamentos' => ['title' => 'Reunião de Ensinamentos da Obra da Piedade', 'order' => 80],
        'organistas' => ['title' => 'Reunião para Organistas', 'order' => 90],
        'ra' => ['title' => 'Reunião das Administrações (RA)', 'order' => 100],
        'rrm' => ['title' => 'Reunião Regional Ministerial (RRM)', 'order' => 110],
        'rga' => ['title' => 'Reunião Geral da Administração', 'order' => 120],
        'rco' => ['title' => 'Reunião de Conscientização e Orientação (RCO)', 'order' => 130],
        'rma' => ['title' => 'Reunião do Ministério e Administração (RMA)', 'order' => 140],
        'santa-ceia' => ['title' => 'Santa Ceia', 'order' => 150],
        'reunioes' => ['title' => 'Reuniões', 'order' => 160],
    ];

    /**
     * Nomes de local do seed RegionalEmbuArtesAgenda2026Seeder::seedLocations.
     *
     * @var array<string, string>
     */
    private const PLACES = [
        'ea-central' => 'Embu das Artes - Central',
        'ea-jardim-da-luz' => 'Jardim da Luz – Embu das Artes',
        'ea-kennedy' => 'Jardim Presidente Kennedy – Embu das Artes',
        'ea-santa-tereza' => 'Jardim Santa Tereza – Embu das Artes',
        'ea-vista-alegre' => 'Jardim Vista Alegre – Embu das Artes',
        'eg-central' => 'Embu-Guaçu - Central',
        'eg-cipo' => 'Cipó-Guaçu – Embu-Guaçu',
        'its-jacira' => 'Jardim Jacira – Itapecerica da Serra',
        'its-marilu' => 'Jardim Marilu – Itapecerica da Serra',
        'its-pq-paraiso' => 'Parque Paraíso – Itapecerica da Serra',
        'its-vila-santa-maria' => 'Vila Santa Maria – Itapecerica da Serra',
        'its-crispim' => 'Crispim – Itapecerica da Serra',
        'juq-central' => 'Juquitiba - Central',
        'juq-barnabes' => 'Barnabés – Juquitiba',
        'juq-justinos' => 'Justinos – Juquitiba',
        'its-central' => 'Itapecerica da Serra - Central',
        'sls-central' => 'São Lourenço da Serra - Central',
        'tbs-iasi' => 'Vila Iasi – Taboão da Serra',
        'tbs-pirajussara' => 'Pirajussara – Taboão da Serra',
        'tbs-saint-moritz' => 'Jardim Saint Moritz – Taboão da Serra',
        'tbs-maria-rosa' => 'Jardim Maria Rosa - Taboão da Serra',
        'bras' => 'Brás',
    ];

    /**
     * @return array{
     *     year: int,
     *     regionalName: string,
     *     seriesCount: int,
     *     eventCount: int,
     *     series: list<array{title: string, events: list<array{title: string, months: string, time: string, place: string}>}>
     * }
     */
    public function compose(int $year = 2026): array
    {
        $rows = $this->expandDateLists(require database_path('seeders/data/agenda_embu_artes_2026.php'));
        $grouped = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $seriesKey = $this->seriesKey($row);
            $eventKey = $seriesKey.'|'.$this->eventKey((string) ($row['title'] ?? ''));
            $months = $this->monthsOf($row);

            if (! isset($grouped[$eventKey])) {
                $grouped[$eventKey] = [
                    'series' => $seriesKey,
                    'title' => $this->displayTitle((string) ($row['title'] ?? '')),
                    'months' => [],
                    'times' => [],
                    'places' => [],
                ];
            }

            foreach ($months as $month) {
                $grouped[$eventKey]['months'][$month] = true;
            }
            $time = trim((string) ($row['time'] ?? ''));
            if ($time !== '') {
                $grouped[$eventKey]['times'][$time] = true;
            }
            $place = $this->placeOf($row);
            if ($place !== '') {
                $grouped[$eventKey]['places'][$place] = true;
            }
        }

        $series = [];
        foreach ($grouped as $event) {
            $key = $event['series'];
            if (! isset($series[$key])) {
                $meta = self::SERIES[$key] ?? ['title' => 'Outros', 'order' => 999];
                $series[$key] = [
                    'title' => $meta['title'],
                    'order' => $meta['order'],
                    'events' => [],
                ];
            }
            $times = array_keys($event['times']);
            sort($times);
            $places = array_keys($event['places']);

            $series[$key]['events'][] = [
                'title' => $event['title'],
                'months' => $this->formatMonths(array_keys($event['months'])),
                'time' => implode(' · ', $times),
                'place' => implode(' · ', $places),
            ];
        }

        uasort($series, fn (array $a, array $b): int => $a['order'] <=> $b['order']);

        $blocks = [];
        $eventCount = 0;
        foreach ($series as $block) {
            $eventCount += count($block['events']);
            $blocks[] = [
                'title' => $block['title'],
                'events' => $block['events'],
            ];
        }

        return [
            'year' => $year,
            'regionalName' => 'Regional Embu das Artes',
            'seriesCount' => count($blocks),
            'eventCount' => $eventCount,
            'series' => $blocks,
        ];
    }

    /**
     * Série e título estável de um evento já gravado (pai ou ocorrência).
     *
     * @return array{groupKey: string, seriesKey: string, seriesTitle: string, order: int, title: string}
     */
    public function identity(Event $event): array
    {
        $meta = is_array($event->meta) ? $event->meta : [];
        $seriesKey = $this->seriesKey([
            'type' => (string) ($event->eventType?->slug ?? ''),
            'excel_key' => (string) ($meta['excel_key'] ?? ''),
        ]);
        $series = self::SERIES[$seriesKey] ?? ['title' => 'Outros', 'order' => 999];

        return [
            'groupKey' => $seriesKey.'|'.$this->eventKey((string) $event->title),
            'seriesKey' => $seriesKey,
            'seriesTitle' => $series['title'],
            'order' => $series['order'],
            'title' => $this->displayTitle((string) $event->title),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function seriesKey(array $row): string
    {
        $type = (string) ($row['type'] ?? '');
        $excelKey = (string) ($row['excel_key'] ?? '');

        return match (true) {
            $type === 'ago' => 'ago',
            $type === 'batismo' => 'batismos',
            $type === 'reuniao-mocidade', $type === 'mocidade-online' => 'mocidade',
            $type === 'culto-jovens-12' => 'jovens',
            $type === 'exame-musical' => 'musical',
            $type === 'ensaio-regional' => 'ensaios',
            $type === 'reuniao-obra-piedade' && str_starts_with($excelKey, 'obra-piedade-mensal') => 'piedade-atendimento',
            $type === 'reuniao-obra-piedade' => 'piedade-ensinamentos',
            $type === 'reuniao-organistas' => 'organistas',
            $type === 'ra' => 'ra',
            $type === 'rrm' => 'rrm',
            $type === 'rga' => 'rga',
            $type === 'rco' => 'rco',
            $type === 'rma' => 'rma',
            $type === 'santa-ceia' => 'santa-ceia',
            default => 'reunioes',
        };
    }

    protected function displayTitle(string $title): string
    {
        $stripped = preg_replace('/\s*\([^)]*\)\s*$/u', '', trim($title));
        $stripped = is_string($stripped) ? trim($stripped) : trim($title);

        return $stripped !== '' ? $stripped : trim($title);
    }

    protected function eventKey(string $title): string
    {
        return mb_strtolower($this->displayTitle($title));
    }

    /**
     * @param  array<string, mixed>  $row
     * @return list<int>
     */
    protected function monthsOf(array $row): array
    {
        $cfg = is_array($row['recurrence'] ?? null) ? $row['recurrence'] : [];
        $frequency = (string) ($cfg['frequency'] ?? 'none');

        if ($frequency === 'monthly_nth') {
            $filter = is_array($cfg['months'] ?? null) ? $cfg['months'] : ['type' => 'all'];
            $months = [];
            for ($month = 1; $month <= 12; $month++) {
                if ($this->monthMatches($month, $filter)) {
                    $months[] = $month;
                }
            }

            return $months;
        }

        $date = (string) ($row['first'] ?? $row['on'] ?? '');
        if (preg_match('/^\d{4}-(\d{2})-\d{2}$/', $date, $match) === 1) {
            return [(int) $match[1]];
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $months
     */
    protected function monthMatches(int $month, array $months): bool
    {
        $type = (string) ($months['type'] ?? 'all');

        return match ($type) {
            'even' => $month % 2 === 0,
            'odd' => $month % 2 === 1,
            'list' => in_array($month, array_map('intval', $months['list'] ?? []), true),
            default => true,
        };
    }

    /**
     * @param  list<int>  $months
     */
    public function formatMonths(array $months): string
    {
        $months = array_values(array_unique(array_map('intval', $months)));
        sort($months);
        $months = array_values(array_filter($months, fn (int $month): bool => isset(self::MONTHS[$month])));

        if ($months === []) {
            return '—';
        }
        if (count($months) === 12) {
            return 'Todos os meses';
        }

        return implode(', ', array_map(fn (int $month): string => self::MONTHS[$month], $months));
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function placeOf(array $row): string
    {
        if (($row['attendance_mode'] ?? null) === 'online_only') {
            return 'Virtual';
        }

        $key = (string) ($row['location_key'] ?? '');

        return self::PLACES[$key] ?? $key;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    protected function expandDateLists(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $dates = $row['on_dates'] ?? null;
            if (! is_array($dates) || $dates === []) {
                $out[] = $row;

                continue;
            }
            foreach (array_values($dates) as $date) {
                $copy = $row;
                unset($copy['on_dates']);
                $copy['on'] = $date;
                $out[] = $copy;
            }
        }

        return $out;
    }
}
