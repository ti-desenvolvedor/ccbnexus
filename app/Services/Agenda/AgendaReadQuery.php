<?php

namespace App\Services\Agenda;

use App\Exceptions\Agenda\AgendaRegionalContextRequired;
use App\Exceptions\Agenda\InvalidAgendaReadCriteria;
use App\Models\Event;
use App\Models\User;
use App\Services\OrganizationalContextService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;

class AgendaReadQuery
{
    public const OCCURRENCE_LIMIT = 500;

    public const MAX_RANGE_DAYS = 62;

    public function __construct(
        protected OrganizationalContextService $context,
    ) {}

    public function read(User $user, AgendaReadCriteria $criteria): AgendaReadResult
    {
        if (! $user->can('viewAny', Event::class)) {
            throw new AuthorizationException;
        }

        $this->assertCriteria($criteria);

        $regionalId = $this->context->activeRegionalId();
        if ($criteria->forPdf && $regionalId === null) {
            throw new AgendaRegionalContextRequired;
        }

        $includesDrafts = $user->isSuperAdmin() || $user->can('editar_evento');
        $range = $criteria->mode === AgendaReadCriteria::MODE_OCCURRENCES
            ? $this->range($criteria)
            : null;

        $query = $this->baseQuery($user, $criteria, $includesDrafts, $regionalId);

        if ($range !== null) {
            [$start, $end] = $range;
            $query->where('events.starts_at', '<', $end->format('Y-m-d H:i:s'))
                ->whereRaw('COALESCE(events.ends_at, events.starts_at) > ?', [$start->format('Y-m-d H:i:s')]);
        }

        if ($criteria->mode === AgendaReadCriteria::MODE_SERIES) {
            $perPage = $criteria->perPage ?? $user->resolvedListPerPage();
            $items = $query
                ->orderByDesc('events.starts_at')
                ->orderByDesc('events.id')
                ->paginate($perPage, ['events.*'], 'page', max(1, $criteria->page));

            return new AgendaReadResult(
                items: $items,
                mode: $criteria->mode,
                window: null,
                rangeStart: null,
                rangeEnd: null,
                regionalId: $regionalId,
                includesDrafts: $includesDrafts,
                truncated: false,
            );
        }

        [$start, $end] = $range;
        $rows = $query
            ->orderBy('events.starts_at')
            ->orderBy('events.id')
            ->limit(self::OCCURRENCE_LIMIT + 1)
            ->get();

        $truncated = $rows->count() > self::OCCURRENCE_LIMIT;

        return new AgendaReadResult(
            items: $truncated ? $rows->take(self::OCCURRENCE_LIMIT)->values() : $rows,
            mode: $criteria->mode,
            window: $criteria->window,
            rangeStart: $start,
            rangeEnd: $end,
            regionalId: $regionalId,
            includesDrafts: $includesDrafts,
            truncated: $truncated,
        );
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function range(AgendaReadCriteria $criteria): array
    {
        $this->assertCriteria($criteria);

        try {
            $cursor = Carbon::parse($criteria->cursor, config('app.timezone'));
        } catch (\Throwable) {
            throw InvalidAgendaReadCriteria::period();
        }

        if ($criteria->window === AgendaReadCriteria::WINDOW_WEEK) {
            $start = $cursor->copy()->startOfWeek();
            $end = $start->copy()->addWeek();
        } else {
            $start = $cursor->copy()->startOfMonth();
            $end = $start->copy()->addMonth();
        }

        if ($start->diffInDays($end) > self::MAX_RANGE_DAYS) {
            throw InvalidAgendaReadCriteria::period();
        }

        return [$start, $end];
    }

    protected function assertCriteria(AgendaReadCriteria $criteria): void
    {
        if (! in_array($criteria->mode, [AgendaReadCriteria::MODE_OCCURRENCES, AgendaReadCriteria::MODE_SERIES], true)) {
            throw InvalidAgendaReadCriteria::period();
        }

        if ($criteria->channel !== '' && ! in_array($criteria->channel, AgendaReadCriteria::CHANNELS, true)) {
            throw InvalidAgendaReadCriteria::channel();
        }

        if ($criteria->forPdf && ($criteria->mode !== AgendaReadCriteria::MODE_OCCURRENCES || $criteria->window !== AgendaReadCriteria::WINDOW_MONTH)) {
            throw InvalidAgendaReadCriteria::period();
        }

        if ($criteria->mode === AgendaReadCriteria::MODE_SERIES) {
            return;
        }

        if (! in_array($criteria->window, [AgendaReadCriteria::WINDOW_WEEK, AgendaReadCriteria::WINDOW_MONTH], true)) {
            throw InvalidAgendaReadCriteria::period();
        }

        if ($criteria->cursor === null || $criteria->cursor === '') {
            throw InvalidAgendaReadCriteria::period();
        }
    }

    /**
     * @return Builder<Event>
     */
    protected function baseQuery(User $user, AgendaReadCriteria $criteria, bool $includesDrafts, ?int $regionalId): Builder
    {
        $allowed = ['published', 'pending_approval'];
        if ($includesDrafts) {
            $allowed[] = 'draft';
        }

        $query = Event::query()
            ->leftJoin('events as agenda_parents', 'events.parent_event_id', '=', 'agenda_parents.id')
            ->select('events.*')
            ->with([
                'regional:id,name',
                'eventType:id,name,slug',
                'location:id,name,city',
                'meetingRoom:id,name',
                'parent:id,status',
                'roleAssignments.roleTemplate:id,slug,name',
                'roleAssignments.user:id,name',
            ])
            ->whereNotIn('events.status', ['cancelled', 'rejected'])
            ->where(function (Builder $status) use ($allowed) {
                $status->where(function (Builder $standalone) use ($allowed) {
                    $standalone->whereNull('events.parent_event_id')
                        ->whereIn('events.status', $allowed);
                })->orWhere(function (Builder $child) use ($allowed) {
                    $child->whereNotNull('events.parent_event_id')
                        ->whereIn('agenda_parents.status', $allowed);
                });
            });

        if ($criteria->mode === AgendaReadCriteria::MODE_SERIES) {
            $query->where('events.is_occurrence', false)
                ->whereNull('events.parent_event_id');
        }

        if (! $user->isSuperAdmin()) {
            $ids = $user->accessibleRegionalIds();
            $query->whereIn('events.regional_id', $ids === [] ? [-1] : $ids);
        }

        if ($regionalId !== null) {
            $query->where('events.regional_id', $regionalId);
        }

        $this->applySharedFilters($query, $criteria);

        return $query;
    }

    /**
     * @param  Builder<Event>  $query
     */
    protected function applySharedFilters(Builder $query, AgendaReadCriteria $criteria): void
    {
        if ($criteria->search !== '') {
            $query->where('events.title', 'like', '%'.$criteria->search.'%');
        }

        if ($criteria->eventTypeId) {
            $query->where('events.event_type_id', $criteria->eventTypeId);
        }

        if ($criteria->channel === 'bulletin') {
            $query->where('events.include_on_baptism_list', true);
        } elseif ($criteria->channel === 'whatsapp') {
            $query->where('events.whatsapp_enabled', true);
        } elseif ($criteria->channel === 'bulletin_only') {
            $query->where('events.include_on_baptism_list', true)
                ->where('events.whatsapp_enabled', false);
        } elseif ($criteria->channel === 'whatsapp_only') {
            $query->where('events.whatsapp_enabled', true)
                ->where('events.include_on_baptism_list', false);
        }

        if (in_array($criteria->status, ['draft', 'pending_approval', 'published', 'cancelled', 'rejected'], true)) {
            $query->where(function (Builder $status) use ($criteria) {
                $status->where(function (Builder $standalone) use ($criteria) {
                    $standalone->whereNull('events.parent_event_id')
                        ->where('events.status', $criteria->status);
                })->orWhere(function (Builder $child) use ($criteria) {
                    $child->whereNotNull('events.parent_event_id')
                        ->where('agenda_parents.status', $criteria->status)
                        ->whereNotIn('events.status', ['cancelled', 'rejected']);
                });
            });
        }
    }
}
