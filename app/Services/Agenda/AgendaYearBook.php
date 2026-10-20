<?php

namespace App\Services\Agenda;

use App\Models\Event;
use App\Models\EventType;
use App\Models\User;
use Carbon\Carbon;

/**
 * Caderno anual da agenda: capa, índice, listagem geral por séries,
 * uma folha por mês, anotações e capa final.
 * A paginação é calculada aqui para o índice citar a página certa.
 */
class AgendaYearBook
{
    public const LISTING_ROWS = 13;

    public const MONTH_FIRST_ROWS = 11;

    public const MONTH_NEXT_ROWS = 18;

    /** @var array<int, string> */
    public const MONTHS = [
        1 => 'Janeiro',
        2 => 'Fevereiro',
        3 => 'Março',
        4 => 'Abril',
        5 => 'Maio',
        6 => 'Junho',
        7 => 'Julho',
        8 => 'Agosto',
        9 => 'Setembro',
        10 => 'Outubro',
        11 => 'Novembro',
        12 => 'Dezembro',
    ];

    public function __construct(
        protected AgendaReadQuery $query,
        protected AgendaMonthLayout $layout,
        protected AgendaSeriesSummary $series,
        protected AgendaHolidayMarkers $holidays,
    ) {}

    /**
     * @return array{
     *     year: int,
     *     regionalName: string,
     *     includesDrafts: bool,
     *     truncated: bool,
     *     filterNote: string,
     *     eventCount: int,
     *     pages: list<array<string, mixed>>
     * }
     */
    public function compose(
        User $user,
        int $year,
        string $search = '',
        ?int $eventTypeId = null,
        string $channel = '',
        string $status = '',
    ): array {
        $months = [];
        $seen = [];
        $summary = [];
        $includesDrafts = false;
        $truncated = false;
        $regionalName = '';
        $holidayDates = [];
        $holidayYearLoaded = false;

        foreach (self::MONTHS as $number => $name) {
            $result = $this->query->read($user, AgendaReadCriteria::pdf(
                cursor: sprintf('%04d-%02d-01', $year, $number),
                search: $search,
                eventTypeId: $eventTypeId,
                channel: $channel,
                status: $status,
            ));
            $includesDrafts = $includesDrafts || $result->includesDrafts;
            $truncated = $truncated || $result->truncated;
            $regionalName = $regionalName !== '' ? $regionalName : (string) ($result->entries()->first()?->regional?->name ?? '');

            if (! $holidayYearLoaded) {
                $holidayDates = $this->holidays->datesForYear($year, $result->regionalId);
                $holidayYearLoaded = true;
            }

            $days = $this->layout->agenda($result);
            $count = $result->entries()->count();
            $months[$number] = [
                'name' => $name,
                'count' => $count,
                'calendar' => $this->calendar($year, $number, $result, $holidayDates),
                'rows' => $this->monthRows($days),
            ];

            foreach ($result->entries() as $event) {
                if (isset($seen[$event->id])) {
                    continue;
                }
                $seen[$event->id] = true;
                $startMonth = (int) $event->starts_at->copy()->timezone(config('app.timezone'))->month;
                $this->rememberSummary($summary, $event, $startMonth);
            }
        }

        $listingRows = $this->summaryRows($summary);

        $listingPages = $this->chunk($listingRows, self::LISTING_ROWS, self::LISTING_ROWS);
        $monthPageGroups = [];
        foreach ($months as $number => $month) {
            $monthPageGroups[$number] = $month['rows'] === []
                ? [[]]
                : $this->chunk($month['rows'], self::MONTH_FIRST_ROWS, self::MONTH_NEXT_ROWS);
        }

        $listingStart = 3;
        $cursor = $listingStart + count($listingPages);
        $monthStarts = [];
        foreach ($monthPageGroups as $number => $group) {
            $monthStarts[$number] = $cursor;
            $cursor += count($group);
        }

        $notesNumber = $cursor;
        $endNumber = $cursor + 1;

        $index = [[
            'label' => 'Listagem geral',
            'page' => $listingStart,
            'detail' => $this->countLabel($this->summaryEventCount($summary)),
        ]];
        foreach (self::MONTHS as $number => $name) {
            $index[] = [
                'label' => $name,
                'page' => $monthStarts[$number],
                'detail' => $this->countLabel($months[$number]['count']),
            ];
        }
        $index[] = [
            'label' => 'Anotações',
            'page' => $notesNumber,
            'detail' => 'linhas para escrever',
        ];

        $pages = [
            ['kind' => 'cover', 'number' => 1],
            ['kind' => 'index', 'number' => 2, 'entries' => $index],
        ];

        $openBand = null;
        foreach ($listingPages as $offset => $rows) {
            if ($rows !== [] && ($rows[0]['kind'] ?? '') !== 'band' && $openBand !== null) {
                array_unshift($rows, $openBand);
            }
            foreach ($rows as $row) {
                if (($row['kind'] ?? '') === 'band') {
                    $openBand = $row;
                }
            }
            $pages[] = [
                'kind' => 'listing',
                'number' => $listingStart + $offset,
                'continued' => $offset > 0,
                'rows' => $rows,
            ];
        }

        foreach (self::MONTHS as $number => $name) {
            foreach ($monthPageGroups[$number] as $offset => $rows) {
                $pages[] = [
                    'kind' => 'month',
                    'number' => $monthStarts[$number] + $offset,
                    'name' => $name,
                    'continued' => $offset > 0,
                    'empty' => $months[$number]['rows'] === [],
                    'calendar' => $offset === 0 ? $months[$number]['calendar'] : [],
                    'hasHoliday' => $offset === 0 && $this->calendarHasHoliday($months[$number]['calendar']),
                    'rows' => $rows,
                ];
            }
        }

        $pages[] = ['kind' => 'notes', 'number' => $notesNumber];
        $pages[] = ['kind' => 'end-cover', 'number' => $endNumber];

        return [
            'year' => $year,
            'regionalName' => $regionalName,
            'includesDrafts' => $includesDrafts,
            'truncated' => $truncated,
            'filterNote' => $this->filterNote($search, $eventTypeId, $channel, $status),
            'eventCount' => count($seen),
            'pages' => $pages,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<list<array<string, mixed>>>
     */
    protected function chunk(array $rows, int $firstCapacity, int $nextCapacity): array
    {
        if ($rows === []) {
            return [[]];
        }

        $pages = [];
        $current = [];
        $limit = $firstCapacity;

        foreach ($rows as $row) {
            $isBand = in_array($row['kind'] ?? '', ['band', 'day'], true);
            $wouldOrphanBand = $isBand && count($current) >= $limit - 1;
            if ($current !== [] && ($wouldOrphanBand || count($current) >= $limit)) {
                $pages[] = $current;
                $current = [];
                $limit = $nextCapacity;
            }
            $current[] = $row;
        }

        if ($current !== []) {
            $pages[] = $current;
        }

        return $pages;
    }

    /**
     * @param  list<array{heading: string, events: list<array<string, mixed>>}>  $days
     * @return list<array<string, mixed>>
     */
    protected function monthRows(array $days): array
    {
        $rows = [];
        foreach ($days as $day) {
            $rows[] = ['kind' => 'day', 'label' => $day['heading']];
            foreach ($day['events'] as $event) {
                $rows[] = ['kind' => 'event'] + $event;
            }
        }

        return $rows;
    }

    /**
     * @param  array<string, true>  $holidayDates
     * @return list<list<array{day: int, inMonth: bool, busy: bool, holiday: bool}>>
     */
    protected function calendar(int $year, int $month, AgendaReadResult $result, array $holidayDates): array
    {
        $tz = config('app.timezone');
        $busy = [];
        foreach ($result->entries() as $event) {
            $busy[$event->starts_at->copy()->timezone($tz)->toDateString()] = true;
        }

        $cursor = Carbon::create($year, $month, 1, 0, 0, 0, $tz)->startOfWeek(Carbon::MONDAY);
        $weeks = [];
        do {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $inMonth = $cursor->month === $month && $cursor->year === $year;
                $week[] = [
                    'day' => $cursor->day,
                    'inMonth' => $inMonth,
                    'busy' => isset($busy[$cursor->toDateString()]),
                    'holiday' => $inMonth && isset($holidayDates[$cursor->toDateString()]),
                ];
                $cursor->addDay();
            }
            $weeks[] = $week;
        } while ($cursor->month === $month && $cursor->year === $year);

        return $weeks;
    }

    /**
     * @param  list<list<array{holiday?: bool}>>  $weeks
     */
    protected function calendarHasHoliday(array $weeks): bool
    {
        foreach ($weeks as $week) {
            foreach ($week as $day) {
                if ($day['holiday'] ?? false) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  array<string, array{series: string, seriesTitle: string, order: int, title: string, months: array<int, true>, times: array<string, true>, places: array<string, true>}>  $summary
     */
    protected function rememberSummary(array &$summary, Event $event, int $month): void
    {
        $identity = $this->series->identity($event);
        $key = $identity['groupKey'];
        if (! isset($summary[$key])) {
            $status = match ($event->effectiveStatus()) {
                'pending_approval' => 'Pendente',
                'draft' => 'Rascunho',
                default => null,
            };
            $summary[$key] = [
                'series' => $identity['seriesKey'],
                'seriesTitle' => $identity['seriesTitle'],
                'order' => $identity['order'],
                'title' => $status ? $identity['title'].' · '.$status : $identity['title'],
                'months' => [],
                'times' => [],
                'places' => [],
            ];
        }

        $summary[$key]['months'][$month] = true;
        $tz = config('app.timezone');
        $start = $event->starts_at->copy()->timezone($tz);
        $end = ($event->ends_at ?? $event->starts_at)->copy()->timezone($tz);
        $summary[$key]['times'][$start->format('H:i').'–'.$end->format('H:i')] = true;
        $place = $event->meetingRoom?->name ?? $event->location?->name;
        if (is_string($place) && $place !== '') {
            $summary[$key]['places'][$place] = true;
        }
    }

    /**
     * @param  array<string, array{series: string, seriesTitle: string, order: int, title: string, months: array<int, true>, times: array<string, true>, places: array<string, true>}>  $summary
     * @return list<array<string, mixed>>
     */
    protected function summaryRows(array $summary): array
    {
        $series = [];
        foreach ($summary as $event) {
            $key = $event['series'];
            if (! isset($series[$key])) {
                $series[$key] = [
                    'title' => $event['seriesTitle'],
                    'order' => $event['order'],
                    'events' => [],
                ];
            }
            $times = array_keys($event['times']);
            sort($times);
            $places = array_keys($event['places']);
            $meta = array_values(array_filter([
                implode(' · ', $times),
                implode(' · ', $places),
            ], fn (string $part): bool => $part !== ''));

            $series[$key]['events'][] = [
                'kind' => 'event',
                'title' => $event['title'],
                'meta' => implode(' · ', $meta),
                'months' => $this->series->formatMonths(array_keys($event['months'])),
                'firstMonth' => $event['months'] === [] ? 99 : min(array_keys($event['months'])),
            ];
        }

        uasort($series, fn (array $a, array $b): int => $a['order'] <=> $b['order']);

        $rows = [];
        foreach ($series as $block) {
            usort($block['events'], fn (array $a, array $b): int => [$a['firstMonth'], mb_strtolower($a['title'])] <=> [$b['firstMonth'], mb_strtolower($b['title'])]);
            $rows[] = ['kind' => 'band', 'label' => $block['title']];
            foreach ($block['events'] as $event) {
                unset($event['firstMonth']);
                $rows[] = $event;
            }
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $summary
     */
    protected function summaryEventCount(array $summary): int
    {
        return count($summary);
    }

    protected function countLabel(int $count): string
    {
        return match ($count) {
            0 => 'nenhum compromisso',
            1 => '1 compromisso',
            default => $count.' compromissos',
        };
    }

    protected function filterNote(string $search, ?int $eventTypeId, string $channel, string $status): string
    {
        $parts = [];
        if ($search !== '') {
            $parts[] = 'pesquisa «'.$search.'»';
        }
        if ($eventTypeId) {
            $name = EventType::query()->whereKey($eventTypeId)->value('name');
            if (is_string($name) && $name !== '') {
                $parts[] = $name;
            }
        }

        $channels = [
            'bulletin' => 'Lista de batismos',
            'whatsapp' => 'WhatsApp',
            'bulletin_only' => 'Só boletim',
            'whatsapp_only' => 'Só WhatsApp',
        ];
        if (isset($channels[$channel])) {
            $parts[] = $channels[$channel];
        }

        $statuses = [
            'draft' => 'Rascunho',
            'pending_approval' => 'Pendente',
            'published' => 'Publicado',
            'cancelled' => 'Cancelado',
            'rejected' => 'Rejeitado',
        ];
        if (isset($statuses[$status])) {
            $parts[] = $statuses[$status];
        }

        return $parts === [] ? '' : 'Filtro: '.implode(' · ', $parts);
    }
}
