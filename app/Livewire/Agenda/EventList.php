<?php

namespace App\Livewire\Agenda;

use App\Exceptions\Agenda\InvalidAgendaReadCriteria;
use App\Livewire\Concerns\UsesAccountPagination;
use App\Models\Event;
use App\Models\EventType;
use App\Models\HolidaySyncLog;
use App\Models\Regional;
use App\Services\Agenda\AgendaHolidayMarkers;
use App\Services\Agenda\AgendaMonthLayout;
use App\Services\Agenda\AgendaReadCriteria;
use App\Services\Agenda\AgendaReadQuery;
use App\Services\Agenda\AgendaReadResult;
use App\Services\Agenda\AgendaWeekLayout;
use App\Services\Agenda\CalendarSync;
use App\Services\Agenda\RecurrenceHorizon;
use App\Services\Agenda\RegionalHolidayPlace;
use App\Services\OrganizationalContextService;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class EventList extends Component
{
    use AuthorizesRequests;
    use UsesAccountPagination;
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    #[Url(except: 'month')]
    public string $view = 'month';

    #[Url(as: 'list', except: 'occurrences')]
    public string $listMode = 'occurrences';

    #[Url(except: '')]
    public string $cursor = '';

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $typeFilter = '';

    #[Url(except: '')]
    public string $channelFilter = '';

    #[Url(except: '')]
    public string $statusFilter = '';

    public bool $periodInvalid = false;

    public string $holidayNotice = '';

    public bool $holidayNoticeIsWarning = false;

    public function mount(): void
    {
        $this->sanitize();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingChannelFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    #[On('nexus-context-updated')]
    public function refreshForContext(): void
    {
        $this->resetPage();
    }

    public function setView(string $view): void
    {
        if (! in_array($view, ['week', 'list', 'month', 'agenda'], true)) {
            return;
        }

        $this->view = $view;
    }

    public function setListMode(string $mode): void
    {
        if (! in_array($mode, ['occurrences', 'series'], true)) {
            return;
        }

        $this->listMode = $mode;
        $this->resetPage();
    }

    public function previousPeriod(): void
    {
        $cursor = $this->resolvedCursor();
        $this->cursor = $this->view === 'week'
            ? $cursor->copy()->startOfWeek()->subWeek()->toDateString()
            : $cursor->copy()->startOfMonth()->subMonth()->toDateString();
        $this->periodInvalid = false;
    }

    public function nextPeriod(): void
    {
        $cursor = $this->resolvedCursor();
        $this->cursor = $this->view === 'week'
            ? $cursor->copy()->startOfWeek()->addWeek()->toDateString()
            : $cursor->copy()->startOfMonth()->addMonth()->toDateString();
        $this->periodInvalid = false;
    }

    public function goToday(): void
    {
        $this->cursor = now()->toDateString();
        $this->periodInvalid = false;
    }

    public function importHolidays(
        CalendarSync $sync,
        RecurrenceHorizon $horizon,
        RegionalHolidayPlace $place,
        OrganizationalContextService $context,
    ): void {
        $this->authorize('create', Event::class);

        $regionalId = $context->activeRegionalId();
        $regional = $regionalId ? Regional::query()->find($regionalId) : null;
        if ($regional === null) {
            $this->holidayNoticeIsWarning = true;
            $this->holidayNotice = 'Selecione uma regional no topo para importar os feriados da cidade.';

            return;
        }

        $where = $place->resolve($regional);
        $log = $sync->syncIfDue([(int) now()->year]);
        $roll = $horizon->extendOpenSeries($regional->id);
        $this->holidayNoticeIsWarning = $where['state'] === null;

        $years = is_array($log->years) && $log->years !== []
            ? array_values($log->years)
            : [(int) now()->year, (int) now()->year + 1];
        $yearLabel = count($years) > 1
            ? $years[0].' e '.$years[array_key_last($years)]
            : (string) $years[0];

        if (! $log->wasRecentlyCreated && $roll['series'] === 0) {
            $when = $log->ran_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '';
            $this->holidayNotice = 'Sincronização deste mês já feita em '.$when.'. O ano atual e o seguinte já estavam carregados e não serão duplicados.';

            return;
        }

        $notice = $log->wasRecentlyCreated
            ? $this->holidayNotice($log->inserted + $log->updated, $yearLabel, $where, $place)
            : 'Feriados de '.$yearLabel.' já estavam sincronizados neste mês.';

        if ($roll['series'] > 0) {
            $through = Carbon::parse($roll['through'])->format('d/m/Y');
            $notice .= ' Recorrências atualizadas até '.$through.': '.$roll['series'].' séries, '.$roll['dates'].' datas novas.';
        }

        $this->holidayNotice = $notice;
    }

    public function render(
        AgendaReadQuery $query,
        AgendaMonthLayout $monthLayout,
        AgendaWeekLayout $weekLayout,
    ) {
        $this->authorize('viewAny', Event::class);
        $this->sanitize();
        $this->normalizeCursor();

        $user = auth()->user();
        app(RecurrenceHorizon::class)->extendOpenSeries(
            app(OrganizationalContextService::class)->activeRegionalId()
        );
        $result = $this->read($query, $user);

        $showPeriod = ! ($this->view === 'list' && $this->listMode === 'series');
        $regional = $result->regionalId
            ? Regional::query()->find($result->regionalId, ['id', 'name', 'slug'])
            : null;

        $canUpdate = [];
        $pendingEvents = [];
        $pendingSeen = [];
        foreach ($result->entries() as $event) {
            $editId = $event->agendaEditId();
            if ($user->can('update', $event)) {
                $canUpdate[$editId] = true;
            }
            if ($event->effectiveStatus() !== 'pending_approval' || isset($pendingSeen[$editId])) {
                continue;
            }
            $pendingSeen[$editId] = true;
            $pendingEvents[] = [
                'editId' => $editId,
                'title' => (string) $event->title,
                'when' => $event->starts_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? 'Data não informada',
                'canUpdate' => $user->can('update', $event),
            ];
        }

        $cursor = $this->resolvedCursor()->toDateString();
        $pdfUrl = $regional
            ? route('agenda.events.pdf', array_filter([
                'cursor' => $cursor,
                'layout' => $this->view === 'agenda' ? 'agenda' : 'month',
                'search' => $this->search,
                'event_type_id' => $this->typeId(),
                'channel' => $this->channelFilter,
                'status' => $this->statusFilter,
            ], fn ($value) => $value !== null && $value !== ''))
            : null;

        $showHolidays = $this->view !== 'list';
        $holidaysByDate = $showHolidays
            ? app(AgendaHolidayMarkers::class)->forRange($result->rangeStart, $result->rangeEnd, $result->regionalId)
            : [];

        return view('livewire.agenda.event-list', [
            'result' => $result,
            'events' => $result->items,
            'activeRegional' => $regional,
            'eventTypes' => EventType::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'name']),
            'showPeriod' => $showPeriod,
            'periodLabel' => $this->view === 'week' ? $weekLayout->label($result) : $monthLayout->monthLabel($result),
            'weeks' => $this->view === 'month' ? $monthLayout->grid($result) : [],
            'agendaDays' => $this->view === 'agenda' ? $monthLayout->agenda($result) : [],
            'weekDays' => $this->view === 'week' ? $weekLayout->days($result) : [],
            'weekHours' => $weekLayout->hours(),
            'weekBlocks' => $this->view === 'week' ? $weekLayout->blocks($result) : [],
            'canUpdate' => $canUpdate,
            'pendingEvents' => $pendingEvents,
            'pdfUrl' => $pdfUrl,
            'pdfYear' => $this->resolvedCursor()->year,
            'canCreate' => $user->can('create', Event::class),
            'holidaysByDate' => $holidaysByDate,
            'periodHasHoliday' => $holidaysByDate !== [],
            'holidaySync' => HolidaySyncLog::query()->where('status', 'success')->latest('ran_at')->first(),
        ]);
    }

    protected function read(AgendaReadQuery $query, $user): AgendaReadResult
    {
        try {
            $invalid = $this->periodInvalid;
            $result = $query->read($user, $this->criteria());
            $this->periodInvalid = $invalid;

            return $result;
        } catch (InvalidAgendaReadCriteria) {
            $this->periodInvalid = true;
            $this->cursor = now()->toDateString();

            return $query->read($user, $this->criteria());
        }
    }

    protected function criteria(): AgendaReadCriteria
    {
        $typeId = $this->typeId();
        $status = in_array($this->statusFilter, ['draft', 'pending_approval', 'published', 'cancelled', 'rejected'], true)
            ? $this->statusFilter
            : '';

        if ($this->view === 'list' && $this->listMode === 'series') {
            return new AgendaReadCriteria(
                mode: AgendaReadCriteria::MODE_SERIES,
                search: $this->search,
                eventTypeId: $typeId,
                channel: $this->channelFilter,
                status: $status,
                page: $this->getPage(),
                perPage: $this->paginatePerPage(),
            );
        }

        return new AgendaReadCriteria(
            mode: AgendaReadCriteria::MODE_OCCURRENCES,
            window: $this->view === 'week' ? AgendaReadCriteria::WINDOW_WEEK : AgendaReadCriteria::WINDOW_MONTH,
            cursor: $this->resolvedCursor()->toDateString(),
            search: $this->search,
            eventTypeId: $typeId,
            channel: $this->channelFilter,
            status: $status,
        );
    }

    protected function typeId(): ?int
    {
        if ($this->typeFilter === '' || $this->typeFilter === '0') {
            return null;
        }

        return (int) $this->typeFilter;
    }

    protected function normalizeCursor(): void
    {
        if ($this->cursor === '') {
            $this->periodInvalid = false;

            return;
        }

        try {
            Carbon::parse($this->cursor, config('app.timezone'));
            $this->periodInvalid = false;
        } catch (\Throwable) {
            $this->periodInvalid = true;
            $this->cursor = now()->toDateString();
        }
    }

    protected function resolvedCursor(): Carbon
    {
        try {
            return $this->cursor !== ''
                ? Carbon::parse($this->cursor, config('app.timezone'))
                : now();
        } catch (\Throwable) {
            return now();
        }
    }

    /**
     * @param  array{city: ?string, state: ?string}  $where
     */
    protected function holidayNotice(int $count, string $yearLabel, array $where, RegionalHolidayPlace $place): string
    {
        $tail = ' Eles aparecem no calendário e ficam fora da lista.';
        if ($where['state'] === null) {
            return 'Feriados nacionais de '.$yearLabel.' cadastrados ('.$count.'). O local da regional não tem UF, então o feriado estadual não aparece.';
        }

        $placeLabel = $where['city']
            ? $where['city'].' ('.$place->stateLabel($where['state']).')'
            : $place->stateLabel($where['state']);

        return $count.' feriados de '.$yearLabel.' cadastrados para '.$placeLabel.': nacionais e o estadual.'.$tail;
    }

    protected function sanitize(): void
    {
        if (! in_array($this->view, ['week', 'list', 'month', 'agenda'], true)) {
            $this->view = 'month';
        }

        if (! in_array($this->listMode, ['occurrences', 'series'], true)) {
            $this->listMode = 'occurrences';
        }

        if (in_array($this->statusFilter, ['cancelled', 'rejected'], true)) {
            $this->statusFilter = '';
        }
    }
}
