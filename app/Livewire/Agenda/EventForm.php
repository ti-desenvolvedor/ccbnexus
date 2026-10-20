<?php

namespace App\Livewire\Agenda;

use App\Models\Event;
use App\Models\EventRoleTemplate;
use App\Models\EventType;
use App\Models\Location;
use App\Models\MeetingRoom;
use App\Models\MinistryServant;
use App\Models\PublicDepartment;
use App\Models\Regional;
use App\Models\WhatsAppGroup;
use App\Models\WhatsAppNoticeTemplate;
use App\Services\EventRecurrenceService;
use App\Services\EventService;
use App\Services\EventTypeAudiencePresetService;
use App\Services\OrganizationalContextService;
use App\Services\OrganizationalLocationService;
use App\Services\PublicAudienceCatalogService;
use App\Support\SimpleRichText;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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

    public bool $include_on_baptism_list = false;

    public bool $has_libras = false;

    /** Pessoa do cadastro de quem atende. Vazio limpa a marcação; "legacy" conserva um nome livre antigo. */
    public string $ministry_servant_id = '';

    public string $unmatchedAttendantLabel = '';

    public string $servant_name = '';

    public string $servant_position_id = '';

    public string $servant_phone = '';

    /** @var array<int, int|string> */
    public array $whatsapp_group_ids = [];

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

    public string $publicAudienceScope = PublicDepartment::SCOPE_REGIONAL;

    public string $cancel_reason = '';

    public string $type_template = '';

    public bool $can_edit_type_template = false;

    public function mount(OrganizationalContextService $context, EventTypeAudiencePresetService $presets): void
    {
        $user = auth()->user();
        $this->can_edit_type_template = (bool) ($user?->isSuperAdmin() || $user?->can('gerenciar_avisos'));

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
            $event->loadMissing(['roleAssignments.roleTemplate', 'whatsappGroups']);
            $this->public_position_ids = $event->publicPositions->pluck('id')->all();
            $this->attendance_mode = $event->attendance_mode ?? 'in_person';
            $this->dress_code = $event->dress_code;
            $this->whatsapp_enabled = (bool) $event->whatsapp_enabled;
            $this->include_on_baptism_list = (bool) $event->include_on_baptism_list;
            $this->has_libras = (bool) $event->has_libras;
            $this->whatsapp_group_ids = $event->whatsappGroups->pluck('id')->all();
            $atende = $event->roleAssignments->first(
                fn ($a) => $a->roleTemplate?->slug === 'atende'
            );
            $this->hydrateAttendant($atende?->assignee_label, data_get($atende?->meta, 'ministry_servant_id'));
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

        $this->loadTypeTemplate($presets);
        $this->adoptTemplateDescription($presets, $this->type_template, false);
    }

    public function updatedRegionalId(): void
    {
        $this->public_position_ids = [];
        $this->ministry_servant_id = '';
        $this->unmatchedAttendantLabel = '';
        $this->reset('servant_name', 'servant_position_id', 'servant_phone');
    }

    public function registerAttendant(): void
    {
        if (! $this->regional_id) {
            $this->addError('servant_name', 'Selecione a regional do evento para cadastrar quem atende.');

            return;
        }

        $user = auth()->user();
        abort_unless($user && ($user->isSuperAdmin() || in_array((int) $this->regional_id, $user->accessibleRegionalIds(), true)), 403);

        $servant = MinistryServant::register(
            (int) $this->regional_id,
            $this->servant_name,
            (int) $this->servant_position_id,
            $this->servant_phone,
        );
        $this->ministry_servant_id = (string) $servant->id;
        $this->unmatchedAttendantLabel = '';
        $this->reset('servant_name', 'servant_position_id', 'servant_phone');
        session()->flash('attendant_status', 'Cadastro guardado. Salve o evento para marcar esta pessoa.');
    }

    public function updatedPublicAudienceScope(): void
    {
        $this->public_position_ids = [];
    }

    public function updatedEventTypeId(EventTypeAudiencePresetService $presets): void
    {
        $previousTemplate = $this->type_template;

        if ($this->event_type_id) {
            $type = EventType::query()->find($this->event_type_id);
            if ($type) {
                $this->include_on_baptism_list = $presets->defaultIncludeOnBaptismList($type);
                $this->whatsapp_enabled = (bool) ($type->meta['default_whatsapp_enabled'] ?? false);
            }
        }

        $this->loadTypeTemplate($presets);
        $this->adoptTemplateDescription($presets, $previousTemplate, true);
    }

    public function loadTypeTemplate(EventTypeAudiencePresetService $presets): void
    {
        if (! $this->event_type_id) {
            $this->type_template = '';

            return;
        }

        $preset = $presets->presetForType((int) $this->event_type_id);
        $this->type_template = (string) ($preset?->invitation_paragraph ?? '');
        $this->dispatch('simple-editor-load', target: 'type_template', html: $this->type_template);
    }

    public function saveTypeTemplate(EventTypeAudiencePresetService $presets): void
    {
        abort_unless($this->can_edit_type_template, 403);

        if (! $this->event_type_id) {
            $this->addError('type_template', 'Selecione o tipo do evento.');

            return;
        }

        $clean = SimpleRichText::sanitize($this->type_template);
        if ($clean !== $this->type_template) {
            $this->type_template = $clean;
            $this->dispatch('simple-editor-load', target: 'type_template', html: $clean);
        }

        $this->validate([
            'type_template' => ['nullable', 'string', 'max:20000'],
        ]);

        $previous = (string) ($presets->presetForType((int) $this->event_type_id)?->invitation_paragraph ?? '');

        $presets->saveParagraph((int) $this->event_type_id, $this->type_template);
        $this->adoptTemplateDescription($presets, $previous, true);
        session()->flash('template_status', 'Modelo deste tipo guardado. As descrições que ainda usavam o texto anterior foram atualizadas.');
    }

    public function applyAudiencePreset(EventTypeAudiencePresetService $presets): void
    {
        if (! $this->event_type_id || ! $this->regional_id) {
            $this->addError('audience_preset', __('Selecione tipo e regional primeiro.'));

            return;
        }
        $preset = $presets->presetForType((int) $this->event_type_id);
        if (! $preset) {
            $this->addError('audience_preset', __('Não há público-modelo para este tipo.'));

            return;
        }
        if ($this->public_position_ids !== []) {
            // Keep existing selection unless empty — user asked for apply; merge positions.
        }
        $ids = $presets->resolvePositionIds($preset, (int) $this->regional_id);
        $this->public_position_ids = array_values(array_unique(array_merge($this->public_position_ids, $ids)));
        $this->adoptTemplateDescription($presets, (string) ($preset->invitation_paragraph ?? ''), true);
    }

    /**
     * A descrição guarda os códigos do modelo ({date}, {location}, …).
     * Só é substituída quando ainda está vazia ou igual ao modelo anterior.
     */
    protected function adoptTemplateDescription(EventTypeAudiencePresetService $presets, string $previousTemplate, bool $refreshEditor): void
    {
        if ($this->type_template === '' || ! $this->descriptionFollowsTemplate($presets, $previousTemplate)) {
            return;
        }

        $this->description = $this->type_template;
        if ($refreshEditor) {
            $this->dispatch('simple-editor-load', target: 'description', html: $this->description);
        }
    }

    protected function descriptionFollowsTemplate(EventTypeAudiencePresetService $presets, string $previousTemplate): bool
    {
        $current = trim($this->description);
        if ($current === '') {
            return true;
        }

        $candidates = array_values(array_unique(array_filter([
            trim($previousTemplate),
            trim($this->type_template),
        ], fn (string $value) => $value !== '')));

        foreach ($candidates as $template) {
            if ($current === $template) {
                return true;
            }
            $rendered = trim($presets->renderTemplate($template, $this->draftEvent()));
            if ($rendered !== '' && $current === $rendered) {
                return true;
            }
        }

        return false;
    }

    protected function draftEvent(): Event
    {
        $draft = new Event([
            'title' => $this->title,
            'regional_id' => $this->regional_id,
            'location_id' => $this->location_id,
        ]);
        if ($starts = $this->formInstant($this->starts_at)) {
            $draft->starts_at = $starts;
        }
        if ($ends = $this->formInstant($this->ends_at)) {
            $draft->ends_at = $ends;
        }
        if ($this->regional_id) {
            $draft->setRelation('regional', Regional::query()->find($this->regional_id));
        }
        if ($this->location_id) {
            $draft->setRelation('location', Location::query()->find($this->location_id));
        }

        return $draft;
    }

    protected function formInstant(string $value): ?Carbon
    {
        if (trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    public function selectSubgroupPositions(int $subgroupId, PublicAudienceCatalogService $catalog): void
    {
        if (! $this->regional_id) {
            return;
        }

        $ids = $catalog->positionIdsForSubgroup((int) $this->regional_id, $subgroupId, $this->publicAudienceScope);
        $this->public_position_ids = array_values(array_unique(array_merge($this->public_position_ids, $ids)));
    }

    public function selectDepartmentPositions(int $departmentId, PublicAudienceCatalogService $catalog): void
    {
        $ids = $catalog->positionIdsForDepartment($departmentId);
        $this->public_position_ids = array_values(array_unique(array_merge($this->public_position_ids, $ids)));
    }

    public function clearPublicPositions(): void
    {
        $this->public_position_ids = [];
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
            ? $catalog->allowedPositionIdsForRegional(auth()->user(), (int) $this->regional_id, $this->publicAudienceScope)
            : [];

        $cleanDescription = SimpleRichText::sanitize($this->description);
        if ($cleanDescription !== $this->description) {
            $this->description = $cleanDescription;
            $this->dispatch('simple-editor-load', target: 'description', html: $cleanDescription);
        }

        try {
            $data = $this->validate([
                'regional_id' => [
                    auth()->user()?->isSuperAdmin() ? 'nullable' : 'required',
                    'exists:regionals,id',
                ],
                'title' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string', 'max:20000'],
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
                'include_on_baptism_list' => ['boolean'],
                'has_libras' => ['boolean'],
                'ministry_servant_id' => [
                    'nullable',
                    'string',
                    function (string $attribute, mixed $value, \Closure $fail): void {
                        if ($value === null || $value === '' || $value === 'legacy') {
                            if ($value === 'legacy' && $this->unmatchedAttendantLabel === '') {
                                $fail('Escolha uma pessoa já cadastrada nesta regional.');
                            }

                            return;
                        }
                        if (! ctype_digit((string) $value)) {
                            $fail('Escolha uma pessoa já cadastrada nesta regional.');

                            return;
                        }
                        $belongs = $this->regional_id && MinistryServant::query()
                            ->whereKey((int) $value)
                            ->where('regional_id', (int) $this->regional_id)
                            ->exists();
                        if (! $belongs) {
                            $fail('Escolha uma pessoa já cadastrada nesta regional.');
                        }
                    },
                ],
                'whatsapp_group_ids' => ['array'],
                'whatsapp_group_ids.*' => ['integer', 'exists:whatsapp_groups,id'],
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
        } catch (ValidationException $exception) {
            $this->tab = $this->tabForValidationKeys(array_keys($exception->errors()));

            throw $exception;
        }

        if (! ($data['whatsapp_enabled'] ?? false)) {
            $data['whatsapp_notice_template_id'] = null;
        } else {
            // Escopo simples: template deve ser global (regional_id null) ou da regional do evento.
            $tpl = ($data['whatsapp_notice_template_id'] ?? null)
                ? WhatsAppNoticeTemplate::query()->find($data['whatsapp_notice_template_id'])
                : null;
            if ($tpl && $tpl->regional_id !== null && ($data['regional_id'] ?? null) !== null && (int) $tpl->regional_id !== (int) $data['regional_id']) {
                $this->addError('whatsapp_notice_template_id', __('Este template não pertence à regional do evento.'));
                $this->tab = 'main';

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

        if (($data['location_id'] ?? null) && ($data['regional_id'] ?? null)) {
            $allowedLocs = $locationScope->mergeCurrentLocation(
                $locationScope->selectableIdsForRegional((int) $data['regional_id']),
                $this->event?->location_id
            );
            if (! in_array((int) $data['location_id'], $allowedLocs, true)) {
                $this->addError('location_id', __('Este local não pertence à regional do evento.'));

                return;
            }
        }
        $user = auth()->user();
        if ($data['meeting_room_id'] ?? null) {
            $room = MeetingRoom::query()->find($data['meeting_room_id']);
            if ($room && ! $user->canAccessMeetingRoom($room)) {
                $this->addError('meeting_room_id', __('Sem permissão para esta sala.'));

                return;
            }
        }

        $ministryServantId = (string) ($data['ministry_servant_id'] ?? '');
        $whatsappGroupIds = collect($data['whatsapp_group_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->values()->all();
        unset($data['ministry_servant_id'], $data['whatsapp_group_ids']);

        if ($this->event) {
            $saved = $events->update($this->event, $data, $user);
            session()->flash('status', __('Evento atualizado.'));
        } else {
            $saved = $events->store($data, $user);
            session()->flash('status', __('Evento criado.'));
        }

        $this->syncAttendantAndGroups($saved, $ministryServantId, $whatsappGroupIds);

        $this->redirect(route('agenda.events.index'), navigate: true);
    }

    /**
     * @param  list<int>  $whatsappGroupIds
     */
    protected function syncAttendantAndGroups(Event $event, string $ministryServantId, array $whatsappGroupIds): void
    {
        $templateId = EventRoleTemplate::query()->where('slug', 'atende')->value('id');
        if ($templateId && $ministryServantId !== 'legacy') {
            $existing = $event->roleAssignments()->where('event_role_template_id', $templateId)->first();
            if ($ministryServantId !== '') {
                $servant = MinistryServant::query()->findOrFail((int) $ministryServantId);
                abort_unless((int) $servant->regional_id === (int) $event->regional_id, 403);
                $servant->assignTo($event);
            } elseif ($existing) {
                $existing->delete();
            }
        }

        $event->whatsappGroups()->sync($whatsappGroupIds);
    }

    protected function hydrateAttendant(mixed $label, mixed $servantId): void
    {
        $label = trim((string) ($label ?? ''));
        $servantId = (int) $servantId;
        if ($servantId <= 0 && $label !== '' && $this->regional_id) {
            $match = MinistryServant::query()
                ->where('regional_id', (int) $this->regional_id)
                ->where('name_key', MinistryServant::nameKey($label))
                ->first();
            $servantId = (int) ($match?->id ?? 0);
        }

        $belongs = $servantId > 0 && $this->regional_id && MinistryServant::query()
            ->whereKey($servantId)
            ->where('regional_id', (int) $this->regional_id)
            ->exists();

        if ($belongs) {
            $this->ministry_servant_id = (string) $servantId;
            $this->unmatchedAttendantLabel = '';

            return;
        }

        $this->ministry_servant_id = $label !== '' ? 'legacy' : '';
        $this->unmatchedAttendantLabel = $label;
    }

    /**
     * @return array<string, list<string>>
     */
    private function tabFieldMap(): array
    {
        return [
            'main' => ['title', 'regional_id', 'event_type_id', 'starts_at', 'ends_at', 'location_id', 'attendance_mode', 'dress_code', 'whatsapp_enabled', 'include_on_baptism_list', 'ministry_servant_id', 'description', 'whatsapp_notice_template_id', 'servant_name', 'servant_position_id', 'servant_phone'],
            'local' => ['meeting_room_id'],
            'audience' => ['public_position_ids'],
            'recurrence' => ['recurrence_frequency', 'recurrence_interval_weeks', 'recurrence_interval_years', 'recurrence_until', 'recurrence_monthly_nth', 'recurrence_monthly_weekday', 'recurrence_months_filter', 'recurrence_months_list'],
            'structure' => ['expected_attendees', 'other_materials_note', 'has_libras'],
            'people' => ['whatsapp_group_ids', 'audience_preset'],
        ];
    }

    /**
     * @param  list<string>  $keys
     */
    private function tabForValidationKeys(array $keys): string
    {
        foreach ($this->tabFieldMap() as $tab => $fields) {
            foreach ($keys as $key) {
                foreach ($fields as $field) {
                    if ($key === $field || str_starts_with($key, $field.'.')) {
                        return $tab;
                    }
                }
            }
        }

        return $this->tab;
    }

    /**
     * @return array<string, int>
     */
    private function tabErrorCounts(): array
    {
        $keys = $this->getErrorBag()->keys();
        $counts = [];

        foreach ($this->tabFieldMap() as $tab => $fields) {
            $counts[$tab] = 0;
            foreach ($keys as $key) {
                foreach ($fields as $field) {
                    if ($key === $field || str_starts_with($key, $field.'.')) {
                        $counts[$tab]++;
                        break;
                    }
                }
            }
        }

        return $counts;
    }

    public function publish(EventService $events, OrganizationalLocationService $locationScope, PublicAudienceCatalogService $catalog): void
    {
        $user = auth()->user();
        $this->status = ($user && ($user->isSuperAdmin() || $user->can('aprovar_evento')))
            ? 'published'
            : 'pending_approval';
        $this->save($events, $locationScope, $catalog);
    }

    public function saveDraft(EventService $events, OrganizationalLocationService $locationScope, PublicAudienceCatalogService $catalog): void
    {
        if ($this->event?->status !== 'published') {
            $this->status = 'draft';
        }

        $this->save($events, $locationScope, $catalog);
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
        OrganizationalContextService $context,
        EventTypeAudiencePresetService $presets,
    ) {
        $user = auth()->user();
        $regionals = $user->isSuperAdmin()
            ? Regional::query()->orderBy('name')->get()
            : Regional::query()->whereIn('id', $user->accessibleRegionalIds())->orderBy('name')->get();

        if ($this->regional_id) {
            $ids = $locationScope->mergeCurrentLocation(
                $locationScope->selectableIdsForRegional((int) $this->regional_id),
                $this->location_id
            );
        } else {
            $regionalIds = $user->isSuperAdmin()
                ? Regional::query()->pluck('id')->all()
                : $user->accessibleRegionalIds();
            $ids = collect($regionalIds)
                ->flatMap(fn (int $rid) => $locationScope->selectableIdsForRegional($rid))
                ->unique()
                ->values()
                ->all();
            $ids = $locationScope->mergeCurrentLocation($ids, $this->location_id);
        }
        $locations = $locationScope->orderedLocationsQuery($ids)->limit(500)->get();

        $administrationId = $this->publicAudienceScope === PublicDepartment::SCOPE_ADMINISTRATION
            ? $context->activeAdministrationId()
            : null;

        $audiencePicker = $catalog->audiencePickerForEvent(
            $user,
            $this->regional_id,
            $this->publicAudienceScope,
            $administrationId,
        );

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
            'audiencePicker' => $audiencePicker,
            'recurrenceSeriesWeekday' => $recurrenceSeriesWeekday,
            'recurrencePreview' => $recurrencePreview,
            'placeholderFields' => $presets->placeholderCatalog(),
            'descriptionPreview' => $presets->containsPlaceholder($this->description)
                ? $presets->renderTemplate($this->description, $this->draftEvent())
                : '',
            'whatsappGroups' => $this->regional_id
                ? WhatsAppGroup::query()
                    ->where('regional_id', $this->regional_id)
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get()
                : collect(),
            'ministryServants' => $this->regional_id
                ? MinistryServant::query()->where('regional_id', (int) $this->regional_id)->orderBy('full_name')->get()
                : collect(),
            'ministryOffices' => $this->regional_id
                ? $catalog->ministryOffices((int) $this->regional_id)
                : collect(),
            'tabErrorCounts' => $this->tabErrorCounts(),
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
