<?php

namespace App\Livewire\Secretaria;

use App\Models\Event;
use App\Models\EventType;
use App\Models\MinistryServant;
use App\Services\MarkingsReportBuilder;
use App\Services\OrganizationalContextService;
use App\Services\PublicAudienceCatalogService;
use Livewire\Attributes\On;
use Livewire\Component;

class MarkingsReportPage extends Component
{
    public string $filter = 'all';

    public string $date_from = '';

    public string $date_to = '';

    public string $other_slug = '';

    public ?int $markingEventId = null;

    public string $servant_name = '';

    public string $servant_position_id = '';

    public string $servant_phone = '';

    public string $existing_servant_id = '';

    /** @var list<string> */
    public array $event_type_slugs = [
        'batismo',
        'santa-ceia',
        'culto-jovens-12',
        'reuniao-mocidade',
        'ensaio-regional',
    ];

    /** @var list<string> */
    public array $extra_slugs = [];

    public function mount(MarkingsReportBuilder $builder): void
    {
        abort_unless(
            auth()->user()?->isSuperAdmin() || auth()->user()?->can('visualizar_relatorios'),
            403
        );
        [$this->date_from, $this->date_to] = $builder->clampDateStrings(now()->toDateString(), null);
    }

    #[On('nexus-context-updated')]
    public function onContextUpdated(): void
    {
        // Re-render with new active regional from session.
    }

    public function updatedDateFrom(MarkingsReportBuilder $builder): void
    {
        [$this->date_from, $this->date_to] = $builder->clampDateStrings($this->date_from, $this->date_to);
    }

    public function updatedDateTo(MarkingsReportBuilder $builder): void
    {
        [$this->date_from, $this->date_to] = $builder->clampDateStrings($this->date_from, $this->date_to);
    }

    public function updatedEventTypeSlugs(): void
    {
        $this->event_type_slugs = array_values(array_intersect(
            $this->event_type_slugs,
            array_keys(MarkingsReportBuilder::DEFAULT_TYPES),
        ));
    }

    public function toggleDefaultType(string $slug): void
    {
        if (! array_key_exists($slug, MarkingsReportBuilder::DEFAULT_TYPES)) {
            return;
        }

        if (in_array($slug, $this->event_type_slugs, true)) {
            $this->event_type_slugs = array_values(array_filter(
                $this->event_type_slugs,
                fn (string $current): bool => $current !== $slug,
            ));

            return;
        }

        $this->event_type_slugs[] = $slug;
    }

    public function updatedOtherSlug(): void
    {
        $slug = $this->other_slug;
        $this->other_slug = '';
        if ($slug === '' || array_key_exists($slug, MarkingsReportBuilder::DEFAULT_TYPES)) {
            return;
        }
        if (! EventType::query()->where('slug', $slug)->exists()) {
            return;
        }
        $this->extra_slugs = array_values(array_unique([...$this->extra_slugs, $slug]));
    }

    public function removeExtra(string $slug): void
    {
        $this->extra_slugs = array_values(array_filter(
            $this->extra_slugs,
            fn (string $current) => $current !== $slug,
        ));
    }

    public function openRegistry(): void
    {
        $this->markingEventId = null;
        $this->reset('servant_name', 'servant_position_id', 'servant_phone', 'existing_servant_id');
        $this->resetErrorBag();
        $this->js("window.dispatchEvent(new CustomEvent('open-modal', { detail: 'marking-attendant' }))");
    }

    public function openAttendant(int $eventId): void
    {
        $event = $this->eventForMarking($eventId);
        $this->markingEventId = $event->id;
        $this->reset('servant_name', 'servant_position_id', 'servant_phone', 'existing_servant_id');
        $this->resetErrorBag();
        $this->js("window.dispatchEvent(new CustomEvent('open-modal', { detail: 'marking-attendant' }))");
    }

    public function saveServant(OrganizationalContextService $context): void
    {
        MinistryServant::register(
            $this->regionalForWrite($context),
            $this->servant_name,
            (int) $this->servant_position_id,
            $this->servant_phone,
        );
        $this->reset('servant_name', 'servant_position_id', 'servant_phone');
        session()->flash('servant_status', 'Cadastro guardado. Ele já pode ser escolhido em Já cadastrado.');
    }

    public function saveAndAssign(OrganizationalContextService $context): void
    {
        $regionalId = $this->regionalForWrite($context);
        $event = $this->eventForMarking((int) $this->markingEventId);
        abort_unless((int) $event->regional_id === $regionalId, 403);

        $servant = MinistryServant::register(
            $regionalId,
            $this->servant_name,
            (int) $this->servant_position_id,
            $this->servant_phone,
        );
        $servant->assignTo($event);
        $this->closeAttendantModal();
        session()->flash('servant_status', 'Quem atende foi marcado.');
    }

    public function useExistingServant(): void
    {
        $this->validate([
            'existing_servant_id' => ['required', 'integer'],
        ], [
            'existing_servant_id.required' => 'Escolha uma pessoa já cadastrada.',
        ]);

        $this->assignServant((int) $this->markingEventId, $this->existing_servant_id);
        $this->closeAttendantModal();
    }

    public function assignServant(int $eventId, string $servantId): void
    {
        if ($servantId === '' || $servantId === 'keep') {
            return;
        }

        $user = auth()->user();
        $event = Event::query()->findOrFail($eventId);
        abort_unless($user && ($user->isSuperAdmin() || in_array((int) $event->regional_id, $user->accessibleRegionalIds(), true)), 403);

        $servant = MinistryServant::query()->findOrFail((int) $servantId);
        abort_unless((int) $servant->regional_id === (int) $event->regional_id, 403);
        $servant->assignTo($event);
    }

    public function render(
        OrganizationalContextService $context,
        MarkingsReportBuilder $builder,
        PublicAudienceCatalogService $catalog,
    ) {
        [$from, $to] = $builder->period($this->date_from, $this->date_to);
        $defaults = array_values(array_intersect($this->event_type_slugs, array_keys(MarkingsReportBuilder::DEFAULT_TYPES)));
        [$defaults, $extras] = $builder->splitSlugs([...$defaults, ...$this->extra_slugs]);
        $regionalId = $context->activeRegionalId();
        $report = $builder->report(
            auth()->user(),
            $regionalId,
            $defaults,
            $extras,
            $from,
            $to,
            $this->filter,
        );

        $names = EventType::query()
            ->whereIn('slug', array_keys(MarkingsReportBuilder::DEFAULT_TYPES))
            ->pluck('name', 'slug');
        $markingTypes = collect(MarkingsReportBuilder::DEFAULT_TYPES)->map(fn (string $fallback, string $slug) => [
            'slug' => $slug,
            'name' => (string) ($names[$slug] ?? $fallback),
        ])->values();

        $otherTypes = EventType::query()
            ->where('is_active', true)
            ->whereNotIn('slug', array_keys(MarkingsReportBuilder::DEFAULT_TYPES))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['slug', 'name']);

        $extraNames = $otherTypes->pluck('name', 'slug');
        $extraTypes = collect($extras)->map(fn (string $slug) => [
            'slug' => $slug,
            'name' => (string) ($extraNames[$slug] ?? $slug),
        ])->values();

        $servants = $regionalId
            ? MinistryServant::query()->where('regional_id', $regionalId)->orderBy('full_name')->get()
            : collect();
        $markingEvent = $this->markingEventId
            ? Event::query()->with(['eventType', 'location'])->find($this->markingEventId)
            : null;

        return view('livewire.secretaria.markings-report-page', [
            'groups' => $report['groups'],
            'from' => $from,
            'to' => $to,
            'today' => now()->toDateString(),
            'markingTypes' => $markingTypes,
            'otherTypes' => $otherTypes,
            'extraTypes' => $extraTypes,
            'servants' => $servants,
            'ministryOffices' => $regionalId ? $catalog->ministryOffices($regionalId) : collect(),
            'markingEvent' => $markingEvent,
            'missingCount' => $report['missing'],
            'canRegisterServant' => (bool) $regionalId,
            'exportQuery' => [
                'date_from' => $from->toDateString(),
                'date_to' => $to->toDateString(),
                'filter' => $this->filter,
                'slugs' => implode(',', [...$defaults, ...$extras]),
            ],
        ]);
    }

    protected function closeAttendantModal(): void
    {
        $this->markingEventId = null;
        $this->reset('servant_name', 'servant_position_id', 'servant_phone', 'existing_servant_id');
        $this->js("window.dispatchEvent(new CustomEvent('close-modal', { detail: 'marking-attendant' }))");
    }

    protected function eventForMarking(int $eventId): Event
    {
        $user = auth()->user();
        $event = Event::query()->findOrFail($eventId);
        abort_unless($user && ($user->isSuperAdmin() || in_array((int) $event->regional_id, $user->accessibleRegionalIds(), true)), 403);

        return $event;
    }

    protected function regionalForWrite(OrganizationalContextService $context): int
    {
        $regionalId = $context->activeRegionalId();
        $user = auth()->user();
        abort_unless($regionalId && $user, 403);
        abort_unless($user->isSuperAdmin() || in_array($regionalId, $user->accessibleRegionalIds(), true), 403);

        return $regionalId;
    }
}
