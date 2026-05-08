<?php

namespace App\Livewire\Agenda;

use App\Models\Event;
use App\Models\EventType;
use App\Models\Location;
use App\Models\MeetingRoom;
use App\Models\Regional;
use App\Models\WhatsAppNoticeTemplate;
use App\Services\EventRecurrenceService;
use App\Services\EventService;
use App\Services\OrganizationalContextService;
use App\Services\OrganizationalLocationService;
use App\Services\PublicAudienceCatalogService;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;

class EventForm extends Component
{
    use AuthorizesRequests;

    public string $tab = 'main';

    public ?Event $event = null;

    public ?int $regional_id = null;

    public string $title = '';

    public string $description = '';

    public ?int $event_type_id = null;

    public string $starts_at = '';

    public string $ends_at = '';

    public ?int $location_id = null;

    public ?int $meeting_room_id = null;

    public string $status = 'draft';

    public ?string $recurrence_frequency = null;

    public ?string $recurrence_until = null;

    public int $recurrence_interval_weeks = 1;

    /** Anual / bienal: intervalo em anos (1 = todos os anos, 2 = a cada 2 anos, …) */
    public int $recurrence_interval_years = 1;

    /** 1–5 = 1.ª … 5.ª semana; -1 = última ocorrência desse dia no mês */
    public int $recurrence_monthly_nth = 2;

    /** 0 = domingo … 6 = sábado (PHP date('w')) */
    public int $recurrence_monthly_weekday = 5;

    /** all | even | odd | list — uma única opção (radio) */
    public string $recurrence_months_filter = 'all';

    /** @var array<int, int> meses 1–12 quando o filtro é «list» */
    public array $recurrence_months_list = [];

    public string $attendance_mode = 'in_person';

    public ?string $dress_code = null;

    public bool $whatsapp_enabled = false;

    public ?int $whatsapp_notice_template_id = null;

    public ?int $expected_attendees = null;

    public bool $needs_sound_controller = false;

    public bool $needs_av = false;

    public bool $needs_parking = false;

    public bool $needs_meals = false;

    public bool $meal_coffee = false;

    public bool $meal_lunch = false;

    public bool $meal_snack = false;

    public bool $meal_dinner = false;

    public bool $needs_nursing = false;

    public bool $needs_valet = false;

    public bool $needs_other_materials = false;

    public string $other_materials_note = '';

    /** @var array<int, int|string> */
    public array $public_position_ids = [];

    public string $cancel_reason = '';

    public function mount(OrganizationalContextService $context): void
    {
        if ($this->event) {
            $event = $this->event;
            if ($event->is_occurrence && $event->parent_event_id) {
                $this->redirect(route('agenda.events.edit', $event->parent_event_id), navigate: true);

                return;
            }
            $this->authorize('update', $event);
            $event->load('publicPositions');
            $this->regional_id = $event->regional_id;
            $this->title = $event->title;
            $this->description = (string) $event->description;
            $this->event_type_id = $event->event_type_id;
            $this->starts_at = $event->starts_at->format('Y-m-d\TH:i');
            $this->ends_at = $event->ends_at->format('Y-m-d\TH:i');
            $this->location_id = $event->location_id;
            $this->meeting_room_id = $event->meeting_room_id;
            $this->status = $event->status;
            $this->recurrence_frequency = $event->recurrence_frequency;
            $this->recurrence_interval_weeks = max(1, min(12, (int) ($event->recurrence_interval_weeks ?? 1)));
            $this->recurrence_until = $event->recurrence_until?->format('Y-m-d');
            if ($event->recurrence_frequency === 'monthly_nth' && is_array($event->recurrence_config)) {
                $cfg = $event->recurrence_config;
                $this->recurrence_monthly_nth = (int) ($cfg['nth'] ?? 2);
                $this->recurrence_monthly_weekday = (int) ($cfg['weekday'] ?? 5);
                $months = is_array($cfg['months'] ?? null) ? $cfg['months'] : [];
                $this->recurrence_months_filter = self::recurrenceMonthsFilterFromStoredMonths($months);
                $this->recurrence_months_list = array_values(array_map('intval', $months['list'] ?? []));
            }
            if ($event->recurrence_frequency === 'yearly' && is_array($event->recurrence_config)) {
                $this->recurrence_interval_years = max(1, min(20, (int) ($event->recurrence_config['interval_years'] ?? 1)));
            }
            $this->public_position_ids = $event->publicPositions->pluck('id')->all();
            $this->attendance_mode = $event->attendance_mode ?? 'in_person';
            $this->dress_code = $event->dress_code;
            $this->whatsapp_enabled = (bool) $event->whatsapp_enabled;
            $this->whatsapp_notice_template_id = $event->whatsapp_notice_template_id;
            $this->expected_attendees = $event->expected_attendees;
            $this->needs_sound_controller = (bool) $event->needs_sound_controller;
            $this->needs_av = (bool) $event->needs_av;
            $this->needs_parking = (bool) $event->needs_parking;
            $this->needs_meals = (bool) $event->needs_meals;
            $this->meal_coffee = (bool) $event->meal_coffee;
            $this->meal_lunch = (bool) $event->meal_lunch;
            $this->meal_snack = (bool) $event->meal_snack;
            $this->meal_dinner = (bool) $event->meal_dinner;
            $this->needs_nursing = (bool) $event->needs_nursing;
            $this->needs_valet = (bool) $event->needs_valet;
            $this->needs_other_materials = (bool) $event->needs_other_materials;
            $this->other_materials_note = (string) ($event->other_materials_note ?? '');
        } else {
            $this->authorize('create', Event::class);
            $this->regional_id = $context->activeRegionalId() ?? auth()->user()?->regional_id;
            $this->starts_at = now()->addDay()->setHour(9)->format('Y-m-d\TH:i');
            $this->ends_at = now()->addDay()->setHour(11)->format('Y-m-d\TH:i');
            $this->dress_code = null;
            $this->whatsapp_enabled = false;
            $this->whatsapp_notice_template_id = null;
        }
    }

    public function updatedRecurrenceFrequency(?string $value): void
    {
        if ($value !== 'weekly' && $value !== 'monthly_nth' && $value !== 'yearly') {
            $this->recurrence_until = null;
            $this->recurrence_interval_weeks = 1;
        }
        if ($value !== 'weekly') {
            $this->recurrence_interval_weeks = 1;
        }
    }

    protected function prepareForValidation($attributes)
    {
        if ($this->expected_attendees === '' || $this->expected_attendees === null) {
            $this->expected_attendees = null;
        }

        $this->recurrence_interval_weeks = max(1, min(12, (int) $this->recurrence_interval_weeks));
        $this->recurrence_interval_years = max(1, min(20, (int) $this->recurrence_interval_years));

        return $attributes;
    }

    public function save(EventService $events, OrganizationalLocationService $locationScope, PublicAudienceCatalogService $catalog): void
    {
        if ($this->event) {
            $this->authorize('update', $this->event);
        } else {
            $this->authorize('create', Event::class);
        }

        if (! $this->regional_id) {
            $this->public_position_ids = [];
        }

        $allowed = $this->regional_id
            ? $catalog->allowedPositionIdsForRegional(auth()->user(), (int) $this->regional_id)
            : [];

        $data = $this->validate([
            'regional_id' => ['nullable', 'exists:regionals,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'event_type_id' => ['nullable', 'exists:event_types,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'meeting_room_id' => ['nullable', 'exists:meeting_rooms,id'],
            'status' => ['required', 'in:draft,pending_approval,published'],
            'recurrence_frequency' => ['nullable', 'in:weekly,monthly_nth,yearly'],
            'recurrence_interval_weeks' => [
                'nullable',
                'integer',
                'min:1',
                'max:12',
                Rule::requiredIf(fn () => $this->recurrence_frequency === 'weekly'),
            ],
            'recurrence_interval_years' => [
                'nullable',
                'integer',
                'min:1',
                'max:20',
                Rule::requiredIf(fn () => $this->recurrence_frequency === 'yearly'),
            ],
            'recurrence_until' => [
                'nullable',
                'date',
                Rule::requiredIf(fn () => in_array($this->recurrence_frequency, ['weekly', 'monthly_nth', 'yearly'], true)),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! in_array($this->recurrence_frequency, ['weekly', 'monthly_nth', 'yearly'], true) || ! $value || $this->starts_at === '') {
                        return;
                    }
                    try {
                        $until = Carbon::parse($value)->startOfDay();
                        $start = Carbon::parse($this->starts_at)->startOfDay();
                        if ($until->lt($start)) {
                            $fail(__('A data de fim da série deve ser igual ou posterior ao dia do primeiro evento.'));
                        }
                    } catch (\Throwable) {
                        $fail(__('Data de fim inválida.'));
                    }
                },
            ],
            'recurrence_monthly_nth' => [
                'nullable',
                'integer',
                Rule::in([-1, 1, 2, 3, 4, 5]),
                Rule::requiredIf(fn () => $this->recurrence_frequency === 'monthly_nth'),
            ],
            'recurrence_monthly_weekday' => [
                'nullable',
                'integer',
                'min:0',
                'max:6',
                Rule::requiredIf(fn () => $this->recurrence_frequency === 'monthly_nth'),
            ],
            'recurrence_months_filter' => [
                'nullable',
                'string',
                Rule::in(['all', 'even', 'odd', 'list']),
                Rule::requiredIf(fn () => $this->recurrence_frequency === 'monthly_nth'),
            ],
            'recurrence_months_list' => [
                'array',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($this->recurrence_frequency !== 'monthly_nth' || $this->recurrence_months_filter !== 'list') {
                        return;
                    }
                    $ids = array_values(array_unique(array_map('intval', is_array($value) ? $value : [])));
                    if ($ids === []) {
                        $fail(__('Selecione pelo menos um mês na grelha.'));
                    }
                },
            ],
            'recurrence_months_list.*' => ['integer', 'min:1', 'max:12'],
            'attendance_mode' => ['required', 'in:in_person,online_only,hybrid'],
            'dress_code' => ['nullable', 'in:social,esporte_fino'],
            'whatsapp_enabled' => ['boolean'],
            'whatsapp_notice_template_id' => [
                'nullable',
                'integer',
                Rule::requiredIf(fn () => (bool) $this->whatsapp_enabled),
                'exists:whatsapp_notice_templates,id',
            ],
            'expected_attendees' => ['nullable', 'integer', 'min:0', 'max:500000'],
            'needs_sound_controller' => ['boolean'],
            'needs_av' => ['boolean'],
            'needs_parking' => ['boolean'],
            'needs_meals' => ['boolean'],
            'meal_coffee' => ['boolean'],
            'meal_lunch' => ['boolean'],
            'meal_snack' => ['boolean'],
            'meal_dinner' => ['boolean'],
            'needs_nursing' => ['boolean'],
            'needs_valet' => ['boolean'],
            'needs_other_materials' => ['boolean'],
            'other_materials_note' => ['nullable', 'string', 'max:2000', 'required_if:needs_other_materials,true'],
            'public_position_ids' => ['array'],
            'public_position_ids.*' => ['integer', Rule::in($allowed)],
        ]);

        if (! ($data['whatsapp_enabled'] ?? false)) {
            $data['whatsapp_notice_template_id'] = null;
        } else {
            // Escopo simples: template deve ser global (regional_id null) ou da regional do evento.
            $tpl = ($data['whatsapp_notice_template_id'] ?? null)
                ? WhatsAppNoticeTemplate::query()->find($data['whatsapp_notice_template_id'])
                : null;
            if ($tpl && $tpl->regional_id !== null && ($data['regional_id'] ?? null) !== null && (int) $tpl->regional_id !== (int) $data['regional_id']) {
                $this->addError('whatsapp_notice_template_id', __('Este template não pertence à regional do evento.'));

                return;
            }
        }

        if (! $data['needs_meals']) {
            $data['meal_coffee'] = false;
            $data['meal_lunch'] = false;
            $data['meal_snack'] = false;
            $data['meal_dinner'] = false;
        }

        if (! $data['needs_other_materials']) {
            $data['other_materials_note'] = null;
        }

        if (($data['attendance_mode'] ?? '') === 'online_only') {
            $data['location_id'] = null;
            $data['meeting_room_id'] = null;
        }

        $monthlyNth = (int) ($data['recurrence_monthly_nth'] ?? 2);
        $monthlyWeekday = (int) ($data['recurrence_monthly_weekday'] ?? 5);
        $monthsFilter = (string) ($data['recurrence_months_filter'] ?? 'all');
        $monthsList = array_values(array_unique(array_map('intval', $data['recurrence_months_list'] ?? [])));
        $intervalYears = max(1, min(20, (int) ($data['recurrence_interval_years'] ?? 1)));

        foreach (['recurrence_monthly_nth', 'recurrence_monthly_weekday', 'recurrence_months_filter', 'recurrence_months_list', 'recurrence_interval_years'] as $key) {
            unset($data[$key]);
        }

        if (empty($data['recurrence_frequency'])) {
            $data['recurrence_frequency'] = null;
            $data['recurrence_until'] = null;
            $data['recurrence_interval_weeks'] = 1;
            $data['recurrence_config'] = null;
        } elseif ($data['recurrence_frequency'] === 'weekly') {
            $data['recurrence_config'] = null;
            $data['recurrence_interval_weeks'] = max(1, min(12, (int) ($data['recurrence_interval_weeks'] ?? 1)));
        } elseif ($data['recurrence_frequency'] === 'monthly_nth') {
            $data['recurrence_config'] = [
                'nth' => $monthlyNth,
                'weekday' => $monthlyWeekday,
                'months' => [
                    'type' => $monthsFilter,
                    'list' => $monthsFilter === 'list' ? $monthsList : [],
                ],
            ];
            $data['recurrence_interval_weeks'] = 1;
        } elseif ($data['recurrence_frequency'] === 'yearly') {
            $data['recurrence_config'] = ['interval_years' => $intervalYears];
            $data['recurrence_interval_weeks'] = 1;
        }

        $user = auth()->user();
        if (! $user->isSuperAdmin() && ($data['location_id'] ?? null) && ($data['regional_id'] ?? null)) {
            $allowedLocs = $locationScope->mergeCurrentLocation(
                $locationScope->selectableIdsForRegional((int) $data['regional_id']),
                $this->event?->location_id
            );
            if (! in_array((int) $data['location_id'], $allowedLocs, true)) {
                $this->addError('location_id', __('Este local não pertence à regional do evento.'));

                return;
            }
        }
        if ($data['meeting_room_id'] ?? null) {
            $room = MeetingRoom::query()->find($data['meeting_room_id']);
            if ($room && ! $user->canAccessMeetingRoom($room)) {
                $this->addError('meeting_room_id', __('Sem permissão para esta sala.'));

                return;
            }
        }

        if ($this->event) {
            $events->update($this->event, $data, $user);
            session()->flash('status', __('Evento atualizado.'));
        } else {
            $events->store($data, $user);
            session()->flash('status', __('Evento criado.'));
        }

        $this->redirect(route('agenda.events.index'), navigate: true);
    }

    public function cancelEvent(EventService $eventsService): void
    {
        if (! $this->event) {
            return;
        }
        $this->validate(['cancel_reason' => ['required', 'string', 'max:2000']]);
        $eventsService->cancel($this->event, auth()->user(), $this->cancel_reason);
        session()->flash('status', __('Evento cancelado.'));
        $this->redirect(route('agenda.events.index'), navigate: true);
    }

    public function render(
        OrganizationalLocationService $locationScope,
        PublicAudienceCatalogService $catalog,
        EventRecurrenceService $recurrenceService,
    ) {
        $user = auth()->user();
        $regionals = $user->isSuperAdmin()
            ? Regional::query()->orderBy('name')->get()
            : Regional::query()->whereIn('id', $user->accessibleRegionalIds())->orderBy('name')->get();

        if ($user->isSuperAdmin()) {
            $locations = Location::query()->orderBy('name')->limit(500)->get();
        } elseif ($this->regional_id) {
            $ids = $locationScope->mergeCurrentLocation(
                $locationScope->selectableIdsForRegional((int) $this->regional_id),
                $this->location_id
            );
            $locations = $locationScope->orderedLocationsQuery($ids)->limit(500)->get();
        } else {
            $ids = collect($user->accessibleRegionalIds())
                ->flatMap(fn (int $rid) => $locationScope->selectableIdsForRegional($rid))
                ->unique()
                ->values()
                ->all();
            $ids = $locationScope->mergeCurrentLocation($ids, $this->location_id);
            $locations = $locationScope->orderedLocationsQuery($ids)->limit(500)->get();
        }

        $positionsGrouped = $catalog->positionsGroupedForEventForm($user, $this->regional_id);

        $recurrenceSeriesWeekday = null;
        if ($this->starts_at !== '') {
            try {
                $recurrenceSeriesWeekday = Carbon::parse($this->starts_at)
                    ->locale(app()->getLocale())
                    ->translatedFormat('l');
            } catch (\Throwable) {
                // ignorar data inválida momentânea no formulário
            }
        }

        $recurrencePreview = null;
        $weekdayLongPt = ['domingo', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado'];
        $monthShortPt = [1 => 'jan.', 2 => 'fev.', 3 => 'mar.', 4 => 'abr.', 5 => 'mai.', 6 => 'jun.', 7 => 'jul.', 8 => 'ago.', 9 => 'set.', 10 => 'out.', 11 => 'nov.', 12 => 'dez.'];

        try {
            if ($this->recurrence_frequency === 'weekly' && $this->recurrence_until && $this->starts_at !== '') {
                $childCount = $recurrenceService->countPlannedWeeklyOccurrences(
                    $this->starts_at,
                    $this->recurrence_until,
                    $this->recurrence_interval_weeks,
                );
                $startDt = Carbon::parse($this->starts_at);
                $endDt = Carbon::parse($this->ends_at ?: $this->starts_at);
                $recurrencePreview = [
                    'type' => 'weekly',
                    'child_count' => $childCount,
                    'total_sessions' => 1 + $childCount,
                    'interval_weeks' => max(1, min(12, $this->recurrence_interval_weeks)),
                    'weekday' => $recurrenceSeriesWeekday,
                    'until_formatted' => Carbon::parse($this->recurrence_until)->format('d/m/Y'),
                    'start_time' => $startDt->format('H:i'),
                    'end_time' => $endDt->format('H:i'),
                ];
            } elseif ($this->recurrence_frequency === 'monthly_nth' && $this->recurrence_until && $this->starts_at !== '') {
                $config = [
                    'nth' => $this->recurrence_monthly_nth,
                    'weekday' => $this->recurrence_monthly_weekday,
                    'months' => [
                        'type' => $this->recurrence_months_filter,
                        'list' => $this->recurrence_months_filter === 'list'
                            ? array_values(array_unique(array_map('intval', $this->recurrence_months_list)))
                            : [],
                    ],
                ];
                $childCount = $recurrenceService->countPlannedMonthlyNthOccurrences(
                    $this->starts_at,
                    $this->recurrence_until,
                    $config,
                );
                $nthLabel = match ($this->recurrence_monthly_nth) {
                    1 => '1.ª',
                    2 => '2.ª',
                    3 => '3.ª',
                    4 => '4.ª',
                    5 => '5.ª',
                    -1 => 'Última',
                    default => '',
                };
                $weekdayLabel = $weekdayLongPt[$this->recurrence_monthly_weekday] ?? '';
                $filterLabel = match ($this->recurrence_months_filter) {
                    'all' => 'todos os meses',
                    'even' => 'só meses pares (fev., abr., …)',
                    'odd' => 'só meses ímpares (jan., mar., …)',
                    'list' => collect($config['months']['list'])->sort()->map(fn (int $m) => $monthShortPt[$m] ?? (string) $m)->implode(', '),
                    default => '',
                };
                $startDt = Carbon::parse($this->starts_at);
                $endDt = Carbon::parse($this->ends_at ?: $this->starts_at);
                $recurrencePreview = [
                    'type' => 'monthly_nth',
                    'child_count' => $childCount,
                    'total_sessions' => 1 + $childCount,
                    'nth_label' => $nthLabel,
                    'weekday_label' => $weekdayLabel,
                    'months_summary' => $filterLabel,
                    'until_formatted' => Carbon::parse($this->recurrence_until)->format('d/m/Y'),
                    'start_time' => $startDt->format('H:i'),
                    'end_time' => $endDt->format('H:i'),
                ];
            } elseif ($this->recurrence_frequency === 'yearly' && $this->recurrence_until && $this->starts_at !== '') {
                $iy = max(1, min(20, $this->recurrence_interval_years));
                $childCount = $recurrenceService->countPlannedYearlyOccurrences(
                    $this->starts_at,
                    $this->recurrence_until,
                    ['interval_years' => $iy],
                );
                $startDt = Carbon::parse($this->starts_at);
                $endDt = Carbon::parse($this->ends_at ?: $this->starts_at);
                $recurrencePreview = [
                    'type' => 'yearly',
                    'child_count' => $childCount,
                    'total_sessions' => 1 + $childCount,
                    'interval_years' => $iy,
                    'until_formatted' => Carbon::parse($this->recurrence_until)->format('d/m/Y'),
                    'start_time' => $startDt->format('H:i'),
                    'end_time' => $endDt->format('H:i'),
                    'anchor_formatted' => $startDt->format('d/m/Y'),
                ];
            }
        } catch (\Throwable) {
            $recurrencePreview = null;
        }

        return view('livewire.agenda.event-form', [
            'regionals' => $regionals,
            'eventTypes' => EventType::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'locations' => $locations,
            'meetingRooms' => MeetingRoom::query()->visibleToUser($user)->where('is_active', true)->orderBy('name')->get(),
            'positionsGrouped' => $positionsGrouped,
            'recurrenceSeriesWeekday' => $recurrenceSeriesWeekday,
            'recurrencePreview' => $recurrencePreview,
        ]);
    }

    /**
     * Converte config antiga (modes) ou actual (type) num único valor para o radio.
     *
     * @param  array<string, mixed>  $months
     */
    protected static function recurrenceMonthsFilterFromStoredMonths(array $months): string
    {
        $allowed = ['all', 'even', 'odd', 'list'];
        if (isset($months['type']) && in_array((string) $months['type'], $allowed, true)) {
            return (string) $months['type'];
        }
        if (! isset($months['modes']) || ! is_array($months['modes']) || $months['modes'] === []) {
            return 'all';
        }
        $modes = array_values(array_unique(array_map('strval', $months['modes'])));
        if (in_array('all', $modes, true)) {
            return 'all';
        }
        if (in_array('list', $modes, true) && isset($months['list']) && is_array($months['list']) && $months['list'] !== []) {
            return 'list';
        }
        if (in_array('even', $modes, true) && in_array('odd', $modes, true)) {
            return 'all';
        }
        foreach (['even', 'odd', 'list'] as $token) {
            if (in_array($token, $modes, true)) {
                return $token;
            }
        }

        return 'all';
    }
}
