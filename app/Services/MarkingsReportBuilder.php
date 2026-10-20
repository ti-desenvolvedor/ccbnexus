<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventType;
use App\Models\MinistryServant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MarkingsReportBuilder
{
    public const DEFAULT_TYPES = [
        'batismo' => 'Batismo',
        'santa-ceia' => 'Santa Ceia',
        'culto-jovens-12' => 'Culto para Jovens (12+)',
        'reuniao-mocidade' => 'Reunião da Mocidade',
        'ensaio-regional' => 'Ensaio Regional',
    ];

    /**
     * @return array{0: string, 1: string}
     */
    public function clampDateStrings(?string $from, ?string $to): array
    {
        [$start, $end] = $this->period($from, $to);

        return [$start->toDateString(), $end->toDateString()];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function period(?string $from, ?string $to): array
    {
        $today = now()->startOfDay();
        try {
            $start = Carbon::parse($from ?: $today->toDateString())->startOfDay();
        } catch (\Throwable) {
            $start = $today->copy();
        }
        if ($start->lt($today)) {
            $start = $today->copy();
        }

        $defaultEnd = now()->addMonths(3)->endOfMonth()->toDateString();
        try {
            $end = Carbon::parse($to !== null && $to !== '' ? $to : $defaultEnd)->endOfDay();
        } catch (\Throwable) {
            $end = Carbon::parse($defaultEnd)->endOfDay();
        }
        if ($end->lt($start)) {
            $end = $start->copy()->endOfDay();
        }

        return [$start, $end];
    }

    /**
     * @param  list<string>  $requested
     * @return array{0: list<string>, 1: list<string>}
     */
    public function splitSlugs(array $requested): array
    {
        $requested = array_values(array_unique(array_filter($requested, fn ($slug) => is_string($slug) && $slug !== '')));
        $defaults = array_values(array_intersect($requested, array_keys(self::DEFAULT_TYPES)));
        $extraCandidates = array_values(array_diff($requested, $defaults));
        $extras = $extraCandidates === []
            ? []
            : EventType::query()->whereIn('slug', $extraCandidates)->orderBy('sort_order')->pluck('slug')->all();

        return [$defaults, array_values($extras)];
    }

    /**
     * @param  list<string>  $defaultSlugs
     * @param  list<string>  $extraSlugs
     * @return array{groups: list<array{slug: string, name: string, rows: list<array<string, mixed>>}>, missing: int}
     */
    public function report(
        User $user,
        ?int $regionalId,
        array $defaultSlugs,
        array $extraSlugs,
        Carbon $from,
        Carbon $to,
        string $status,
    ): array {
        [$defaultSlugs, $extraSlugs] = $this->splitSlugs([...$defaultSlugs, ...$extraSlugs]);
        if ($defaultSlugs === [] && $extraSlugs === []) {
            return ['groups' => [], 'missing' => 0];
        }

        $query = Event::query()
            ->with(['eventType', 'location', 'roleAssignments.roleTemplate', 'roleAssignments.user'])
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->whereBetween('starts_at', [$from, $to]);

        $query->where(function ($outer) use ($defaultSlugs, $extraSlugs) {
            if ($defaultSlugs !== []) {
                $outer->where(function ($inner) use ($defaultSlugs) {
                    $inner->where('include_on_baptism_list', true)
                        ->whereHas('eventType', fn ($type) => $type->whereIn('slug', $defaultSlugs));
                });
            }
            if ($extraSlugs !== []) {
                $method = $defaultSlugs === [] ? 'whereHas' : 'orWhereHas';
                $outer->{$method}('eventType', fn ($type) => $type->whereIn('slug', $extraSlugs));
            }
        });

        if ($regionalId) {
            $query->where('regional_id', $regionalId);
        } elseif (! $user->isSuperAdmin()) {
            $query->whereIn('regional_id', $user->accessibleRegionalIds());
        }

        $events = $query->get()->reject(fn (Event $event) => $this->locationIsBras($event))->values();
        $regionalIds = $events->pluck('regional_id')->filter()->unique()->all();
        $servants = MinistryServant::query()
            ->when($regionalIds !== [], fn ($q) => $q->whereIn('regional_id', $regionalIds))
            ->get();
        $servantsById = $servants->keyBy('id');
        $servantsByName = $servants->keyBy(fn (MinistryServant $servant) => $servant->regional_id.'|'.$servant->name_key);

        $rows = $events->map(function (Event $event) use ($servantsById, $servantsByName) {
            $assignment = $event->roleAssignments->first(
                fn ($role) => $role->roleTemplate?->slug === 'atende'
            );
            $servantId = (int) data_get($assignment?->meta, 'ministry_servant_id');
            $servant = $servantId ? $servantsById->get($servantId) : null;
            $attendant = $event->attendantLabel();
            if (! $servant && filled($attendant)) {
                $servant = $servantsByName->get($event->regional_id.'|'.MinistryServant::nameKey($attendant));
            }
            $marked = filled($attendant) && $attendant !== '— — — — — —';

            return [
                'event_id' => $event->id,
                'edit_id' => $event->is_occurrence ? $event->parent_event_id : $event->id,
                'regional_id' => (int) $event->regional_id,
                'starts_at' => $event->starts_at,
                'when' => $event->starts_at?->format('d/m/Y H:i') ?? '',
                'type_slug' => (string) ($event->eventType?->slug ?? ''),
                'type_name' => (string) ($event->eventType?->name ?? 'Evento'),
                'type_sort' => (int) ($event->eventType?->sort_order ?? 9999),
                'place' => trim(($event->location?->name ?? $event->title).(data_get($event->meta, 'provisional') || str_starts_with((string) $event->title, '*') ? ' *' : '')),
                'attendant' => $marked ? (string) $attendant : '',
                'marked' => $marked,
                'ministry' => $servant?->ministry ?? '',
                'phone' => $servant?->formattedPhone() ?? '',
                'servant_id' => $servant?->id,
            ];
        });

        if ($status === 'missing') {
            $rows = $rows->where('marked', false)->values();
        } elseif ($status === 'ok') {
            $rows = $rows->where('marked', true)->values();
        }

        $rows = $rows->sortBy(fn (array $row) => sprintf(
            '%05d|%s|%010d|%s|%010d',
            $row['type_sort'],
            $row['type_name'],
            $row['starts_at']?->getTimestamp() ?? 0,
            Str::ascii(mb_strtolower((string) $row['place'])),
            (int) $row['event_id'],
        ))->values();

        $groups = $rows
            ->groupBy('type_slug')
            ->map(fn (Collection $group, string $slug) => [
                'slug' => $slug,
                'name' => (string) ($group->first()['type_name'] ?? $slug),
                'rows' => $group->values()->all(),
            ])
            ->values()
            ->all();

        return [
            'groups' => $groups,
            'missing' => $rows->where('marked', false)->count(),
        ];
    }

    /**
     * Same location label as the baptism list: name and city, or the title when
     * there is no location. "brás" and unaccented "bras" are another regional
     * that Embu displays but does not mark.
     */
    public function locationIsBras(Event $event): bool
    {
        $label = $event->location
            ? BaptismListReportBuilder::locationLabel($event->location->name, $event->location->city)
            : (string) ($event->title ?? '');
        $label = mb_strtolower($label);

        return str_contains($label, 'brás') || str_contains($label, 'bras');
    }
}
