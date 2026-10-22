<?php

namespace App\Services\Dashboard;

use App\Models\AccessRequest;
use App\Models\Administration;
use App\Models\Approval;
use App\Models\BaptismListReport;
use App\Models\Event;
use App\Models\EventRsvp;
use App\Models\MeetingRoom;
use App\Models\MinistryServant;
use App\Models\PrayerHouse;
use App\Models\PublicPosition;
use App\Models\Regional;
use App\Models\RoomReservation;
use App\Models\User;
use App\Services\OrganizationalContextService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class OfficeOverview
{
    public function __construct(
        protected OrganizationalContextService $context,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function snapshot(User $user): array
    {
        $regionalId = $this->context->activeRegionalId();
        $regionalName = $regionalId
            ? Regional::query()->whereKey($regionalId)->value('name')
            : null;

        $weekStart = now()->startOfWeek(Carbon::SUNDAY)->startOfDay();
        $weekEnd = $weekStart->copy()->addDays(6)->endOfDay();
        $horizonEnd = $weekStart->copy()->addWeeks(8)->subSecond();
        $upcomingEnd = $weekEnd->copy()->addDays(21);
        $weekLabel = $weekStart->locale('pt_BR')->translatedFormat('d/m').' a '.$weekEnd->locale('pt_BR')->translatedFormat('d/m');

        $canSeeEvents = $user->can('viewAny', Event::class);
        $canSeeMembers = $user->isSuperAdmin() || $user->can('visualizar_relatorios') || $user->can('editar_evento');
        $canSeeCatalog = $user->can('viewAny', PublicPosition::class) || $canSeeMembers;
        $canSeeHouses = $user->can('viewAny', PrayerHouse::class);
        $canSeeAdministrations = $user->can('viewAny', Administration::class);
        $canSeeUsers = $user->can('viewAny', User::class);
        $canSeeAccess = $user->can('viewAny', AccessRequest::class);
        $canSeeApprovals = $user->can('viewAny', Approval::class);
        $canSeeReservations = $user->can('viewAny', RoomReservation::class);
        $canSeeLists = $user->isSuperAdmin() || $user->can('visualizar_relatorios');

        $weekEvents = [];
        $upcomingEvents = [];
        $weekChart = [];
        $eventStatus = ['published' => 0, 'pending' => 0, 'draft' => 0];
        $confirmedPresences = 0;

        if ($canSeeEvents) {
            $dated = $this->datedEvents($user, $regionalId)
                ->whereBetween('starts_at', [$weekStart, $horizonEnd])
                ->get(['id', 'starts_at', 'status']);

            $weekChart = $this->weekBuckets($weekStart, $dated);
            $eventStatus = [
                'published' => $dated->where('status', 'published')->count(),
                'pending' => $dated->where('status', 'pending_approval')->count(),
                'draft' => $dated->where('status', 'draft')->count(),
            ];

            $weekEvents = $this->mapEvents(
                $this->datedEvents($user, $regionalId)
                    ->with(['location:id,name', 'eventType:id,name,slug', 'roleAssignments.roleTemplate:id,slug', 'roleAssignments.user:id,name'])
                    ->whereBetween('starts_at', [$weekStart, $weekEnd])
                    ->orderBy('starts_at')
                    ->limit(40)
                    ->get(),
                $user,
            );

            $upcomingEvents = $this->mapEvents(
                $this->datedEvents($user, $regionalId)
                    ->with(['location:id,name', 'eventType:id,name,slug', 'roleAssignments.roleTemplate:id,slug', 'roleAssignments.user:id,name'])
                    ->where('starts_at', '>', $weekEnd)
                    ->where('starts_at', '<=', $upcomingEnd)
                    ->orderBy('starts_at')
                    ->limit(30)
                    ->get(),
                $user,
            );

            $confirmedPresences = EventRsvp::query()
                ->where('participation', EventRsvp::PARTICIPATION_YES)
                ->whereHas('event', function (Builder $query) use ($user, $regionalId, $weekStart, $weekEnd) {
                    $this->constrainEvents($query, $user, $regionalId);
                    $query->whereBetween('starts_at', [$weekStart, $weekEnd])
                        ->whereNotIn('status', ['cancelled', 'rejected']);
                })
                ->count();
        }

        $memberCount = 0;
        $ministryCount = 0;
        $ministryChart = [];
        if ($canSeeMembers) {
            $members = $this->members($user, $regionalId);
            $memberCount = (clone $members)->count();
            $rows = (clone $members)
                ->selectRaw('ministry, COUNT(*) as total')
                ->groupBy('ministry')
                ->get();
            $buckets = [];
            foreach ($rows as $row) {
                $label = trim((string) $row->ministry);
                if ($label === '') {
                    $label = 'Sem cargo';
                }
                $buckets[$label] = ($buckets[$label] ?? 0) + (int) $row->total;
            }
            $grouped = collect($buckets)
                ->map(fn (int $total, string $label): array => ['label' => $label, 'value' => $total])
                ->sortBy([
                    ['value', 'desc'],
                    ['label', 'asc'],
                ])
                ->values();
            $ministryCount = $grouped->count();
            $ministryChart = $this->withPercent($grouped->take(6)->all());
        }

        $positionCount = $canSeeCatalog ? $this->positionCount($user, $regionalId) : 0;
        $houseCount = $canSeeHouses ? $this->houses($user, $regionalId)->count() : 0;
        $administrationCount = $canSeeAdministrations ? $this->administrations($user, $regionalId)->count() : 0;
        $userCount = $canSeeUsers ? $this->users($user, $regionalId)->count() : 0;

        $pendingAccess = $canSeeAccess
            ? $this->accessRequests($user, $regionalId)->where('status', 'pending')->count()
            : 0;

        $pendingApprovals = $canSeeApprovals
            ? Approval::query()
                ->where('status', 'pending')
                ->where('approvable_type', Event::class)
                ->whereHasMorph('approvable', [Event::class], function (Builder $query) use ($user, $regionalId) {
                    $this->constrainEvents($query, $user, $regionalId);
                })
                ->count()
            : 0;

        $reservationRows = [];
        $pendingReservations = 0;
        if ($canSeeReservations) {
            $roomIds = $this->roomIds($user, $regionalId);
            $pendingReservations = RoomReservation::query()
                ->whereIn('meeting_room_id', $roomIds)
                ->where('status', 'pending')
                ->count();
            $reservationRows = RoomReservation::query()
                ->with('meetingRoom:id,name')
                ->whereIn('meeting_room_id', $roomIds)
                ->whereBetween('starts_at', [$weekStart, $weekEnd])
                ->whereNotIn('status', ['cancelled', 'rejected'])
                ->orderBy('starts_at')
                ->limit(20)
                ->get()
                ->map(fn (RoomReservation $reservation) => [
                    'id' => $reservation->id,
                    'title' => $reservation->title,
                    'when' => $reservation->starts_at->locale('pt_BR')->translatedFormat('D d/m · H:i'),
                    'place' => $reservation->meetingRoom?->name,
                    'status' => match ($reservation->status) {
                        'approved' => 'Confirmada',
                        'pending' => 'Pendente',
                        'draft' => 'Rascunho',
                        default => ucfirst((string) $reservation->status),
                    },
                    'tone' => match ($reservation->status) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        default => 'neutral',
                    },
                ])
                ->all();
        }

        $missingAttendant = 0;
        if ($canSeeEvents && $canSeeMembers) {
            $missingAttendant = $this->datedEvents($user, $regionalId)
                ->whereBetween('starts_at', [$weekStart, $weekEnd])
                ->whereIn('status', ['published', 'pending_approval'])
                ->whereDoesntHave('roleAssignments', function (Builder $query) {
                    $query->whereHas('roleTemplate', fn (Builder $template) => $template->where('slug', 'atende'));
                })
                ->count();
        }

        $pendingLists = [];
        $approvedLists = [];
        if ($canSeeLists && $regionalId) {
            [$pendingLists, $approvedLists] = $this->baptismListSummaries(
                BaptismListReport::query()->withCount('items')->where('regional_id', $regionalId)
            );
        } elseif ($canSeeLists) {
            [$pendingLists, $approvedLists] = $this->baptismListSummaries(
                $this->lists($user, null)->withCount('items')
            );
        }

        $weekCount = $canSeeEvents
            ? $this->datedEvents($user, $regionalId)->whereBetween('starts_at', [$weekStart, $weekEnd])->count()
            : 0;
        $upcomingCount = $canSeeEvents
            ? $this->datedEvents($user, $regionalId)
                ->where('starts_at', '>=', now()->startOfDay())
                ->where('starts_at', '<=', now()->addDays(21)->endOfDay())
                ->count()
            : 0;

        return [
            'regionalName' => $regionalName,
            'weekLabel' => $weekLabel,
            'scopeLabel' => $regionalName ?: 'Todas as regionais do seu acesso',
            'weekEvents' => $weekEvents,
            'upcomingEvents' => $upcomingEvents,
            'weekChart' => $weekChart,
            'ministryChart' => $ministryChart,
            'ministryRemainder' => max(0, $ministryCount - count($ministryChart)),
            'eventStatus' => $eventStatus,
            'confirmedPresences' => $confirmedPresences,
            'pendingLists' => $pendingLists,
            'approvedLists' => $approvedLists,
            'reservations' => $reservationRows,
            'secretariaLinks' => $this->secretariaLinksFor($user),
            'canCreateEvent' => $user->can('create', Event::class),
            'canSeeEvents' => $canSeeEvents,
            'canSeeMembers' => $canSeeMembers,
            'canSeeLists' => $canSeeLists,
            'canSeeReservations' => $canSeeReservations,
            'canSeeApprovals' => $canSeeApprovals,
            'stats' => array_values(array_filter([
                $canSeeEvents ? $this->stat('Cultos da semana', $weekCount, $weekLabel, route('agenda.events.index')) : null,
                $canSeeEvents ? $this->stat('Próximos 21 dias', $upcomingCount, 'a partir de hoje', route('agenda.events.index')) : null,
                $canSeeMembers ? $this->stat('Cadastros', $memberCount, 'membros do ministério', route('secretaria.quem-atende')) : null,
                $canSeeMembers ? $this->stat('Ministérios', $ministryCount, 'funções com pessoas', route('agenda.public-catalog.ministry')) : null,
                $canSeeCatalog ? $this->stat('Cargos', $positionCount, 'cargos ativos no catálogo', $user->can('viewAny', PublicPosition::class) ? route('agenda.public-positions.index') : null) : null,
                $canSeeHouses ? $this->stat('Casas de oração', $houseCount, 'ativas neste recorte', route('organization.prayer-houses.index')) : null,
                $canSeeAdministrations ? $this->stat('Administrações', $administrationCount, 'ativas neste recorte', route('organization.administrations.index')) : null,
                $canSeeUsers ? $this->stat('Usuários', $userCount, 'com acesso ao sistema', route('users.index')) : null,
            ])),
            'attention' => array_values(array_filter([
                $pendingApprovals > 0 ? $this->attention('Aprovações de evento', $pendingApprovals, route('agenda.approvals.index'), 'warning') : null,
                $pendingAccess > 0 ? $this->attention('Pedidos de acesso', $pendingAccess, route('access.requests.index'), 'warning') : null,
                $pendingReservations > 0 ? $this->attention('Reservas pendentes', $pendingReservations, route('infrastructure.room-reservations.index'), 'warning') : null,
                $missingAttendant > 0 ? $this->attention(
                    $missingAttendant === 1 ? 'culto sem quem atende' : 'cultos sem quem atende',
                    $missingAttendant,
                    route('secretaria.quem-atende'),
                    'danger',
                ) : null,
            ])),
        ];
    }

    /**
     * @param  array{label: string, value: int, hint: string, href: ?string}  $stat
     * @return array{label: string, value: int, hint: string, href: ?string}
     */
    private function stat(string $label, int $value, string $hint, ?string $href): array
    {
        return compact('label', 'value', 'hint', 'href');
    }

    /**
     * @return array{label: string, value: int, href: string, tone: string}
     */
    private function attention(string $label, int $value, string $href, string $tone): array
    {
        return compact('label', 'value', 'href', 'tone');
    }

    private function datedEvents(User $user, ?int $regionalId): Builder
    {
        $query = Event::query()->whereNotIn('status', ['cancelled', 'rejected']);
        $this->constrainEvents($query, $user, $regionalId);

        return $query;
    }

    private function constrainEvents(Builder $query, User $user, ?int $regionalId): void
    {
        if (! $user->isSuperAdmin()) {
            $ids = $user->accessibleRegionalIds();
            $query->whereIn('regional_id', $ids === [] ? [-1] : $ids);
        }

        if ($regionalId) {
            $query->where('regional_id', $regionalId);
        }
    }

    /**
     * @param  Collection<int, Event>  $events
     * @return list<array<string, mixed>>
     */
    private function mapEvents(Collection $events, User $user): array
    {
        return $events->map(function (Event $event) use ($user) {
            return [
                'id' => $event->id,
                'title' => $event->title,
                'when' => $event->starts_at->locale('pt_BR')->translatedFormat('D d/m · H:i'),
                'place' => $event->location?->name,
                'type' => $event->eventType?->name,
                'showStatus' => ! in_array(mb_strtolower((string) ($event->eventType?->slug ?? $event->eventType?->name ?? '')), ['batismo'], true),
                'attendant' => $event->attendantLabel(),
                'status' => $event->statusLabel(),
                'tone' => $event->statusTone(),
                'href' => $user->can('update', $event)
                    ? route('agenda.events.edit', $event)
                    : route('agenda.events.index'),
            ];
        })->all();
    }

    /**
     * @param  Collection<int, Event>  $dated
     * @return list<array{label: string, value: int, percent: int}>
     */
    private function weekBuckets(Carbon $weekStart, Collection $dated): array
    {
        $rows = [];
        for ($i = 0; $i < 8; $i++) {
            $start = $weekStart->copy()->addWeeks($i);
            $end = $start->copy()->addDays(6)->endOfDay();
            $rows[] = [
                'label' => $start->format('d/m'),
                'value' => $dated->filter(fn (Event $event) => $event->starts_at >= $start && $event->starts_at <= $end)->count(),
            ];
        }

        return $this->withPercent($rows);
    }

    /**
     * @param  list<array{label: string, value: int}>  $rows
     * @return list<array{label: string, value: int, percent: int}>
     */
    private function withPercent(array $rows): array
    {
        $max = max(1, ...array_map(fn (array $row) => $row['value'], $rows ?: [['value' => 0]]));

        return array_map(function (array $row) use ($max) {
            $row['percent'] = $row['value'] === 0 ? 0 : (int) max(8, round($row['value'] / $max * 100));

            return $row;
        }, $rows);
    }

    private function members(User $user, ?int $regionalId): Builder
    {
        $query = MinistryServant::query();
        $this->constrainRegional($query, $user, $regionalId);

        return $query;
    }

    private function positionCount(User $user, ?int $regionalId): int
    {
        $query = PublicPosition::query()->active();
        $ids = $regionalId ? [$regionalId] : ($user->isSuperAdmin() ? null : $user->accessibleRegionalIds());
        if ($ids === []) {
            return 0;
        }
        if ($ids === null) {
            return $query->count();
        }

        $query->where(function (Builder $outer) use ($ids) {
            foreach ($ids as $id) {
                $outer->orWhere(fn (Builder $inner) => $inner->forRegionalContext($id));
            }
        });

        return $query->count();
    }

    private function houses(User $user, ?int $regionalId): Builder
    {
        $query = PrayerHouse::query()->where('is_active', true);
        $ids = $this->regionalIds($user, $regionalId);
        if ($ids === []) {
            return $query->whereRaw('0 = 1');
        }
        if ($ids !== null) {
            $query->whereHas('administration', fn (Builder $admin) => $admin->whereIn('regional_id', $ids));
        }

        return $query;
    }

    private function administrations(User $user, ?int $regionalId): Builder
    {
        $query = Administration::query()->where('is_active', true);
        $this->constrainRegional($query, $user, $regionalId, 'regional_id');

        return $query;
    }

    private function users(User $user, ?int $regionalId): Builder
    {
        $query = User::query()->where('is_super_admin', false);
        $ids = $this->regionalIds($user, $regionalId);
        if ($ids === []) {
            return $query->whereRaw('0 = 1');
        }
        if ($ids !== null) {
            $query->where(function (Builder $inner) use ($ids) {
                $inner->whereIn('regional_id', $ids)
                    ->orWhereHas('administration', fn (Builder $admin) => $admin->whereIn('regional_id', $ids))
                    ->orWhereHas('prayerHouse.administration', fn (Builder $admin) => $admin->whereIn('regional_id', $ids));
            });
        }

        return $query;
    }

    private function accessRequests(User $user, ?int $regionalId): Builder
    {
        $query = AccessRequest::query();
        $this->constrainRegional($query, $user, $regionalId);

        return $query;
    }

    private function lists(User $user, ?int $regionalId): Builder
    {
        $query = BaptismListReport::query();
        $this->constrainRegional($query, $user, $regionalId);

        return $query;
    }

    private function constrainRegional(Builder $query, User $user, ?int $regionalId, string $column = 'regional_id'): void
    {
        $ids = $this->regionalIds($user, $regionalId);
        if ($ids === []) {
            $query->whereRaw('0 = 1');

            return;
        }
        if ($ids !== null) {
            $query->whereIn($column, $ids);
        }
    }

    /**
     * @return list<int>|null
     */
    private function regionalIds(User $user, ?int $regionalId): ?array
    {
        if ($regionalId) {
            return [$regionalId];
        }

        if ($user->isSuperAdmin()) {
            return null;
        }

        return $user->accessibleRegionalIds();
    }

    /**
     * @return Collection<int, int>
     */
    private function roomIds(User $user, ?int $regionalId): Collection
    {
        $query = MeetingRoom::query()->visibleToUser($user);
        if ($regionalId) {
            $query->where(function (Builder $outer) use ($regionalId) {
                $outer->whereHasMorph('owner', [Administration::class], fn (Builder $admin) => $admin->where('regional_id', $regionalId))
                    ->orWhereHasMorph('owner', [PrayerHouse::class], function (Builder $house) use ($regionalId) {
                        $house->whereHas('administration', fn (Builder $admin) => $admin->where('regional_id', $regionalId));
                    });
            });
        }

        return $query->pluck('id');
    }

    /**
     * @return array{0: list<array<string, mixed>>, 1: list<array<string, mixed>>}
     */
    private function baptismListSummaries(Builder $lists): array
    {
        $order = function (Builder $query): Builder {
            return $query
                ->orderByRaw('COALESCE(generated_at, created_at) DESC')
                ->orderByDesc('id')
                ->limit(5);
        };

        $pending = $order((clone $lists)->where('status', BaptismListReport::STATUS_DRAFT))
            ->get()
            ->map(fn (BaptismListReport $report) => $this->listRow($report))
            ->all();
        $approved = $order((clone $lists)->where('status', BaptismListReport::STATUS_READY))
            ->get()
            ->map(fn (BaptismListReport $report) => $this->listRow($report))
            ->all();

        return [$pending, $approved];
    }

    /**
     * @return array<string, mixed>
     */
    private function listRow(BaptismListReport $report): array
    {
        $items = (int) $report->items_count;

        return [
            'id' => $report->id,
            'revision' => 'Revisão '.$report->revision,
            'issue' => $report->issue_date->format('d/m/Y'),
            'period' => $report->period_start->format('d/m').' a '.$report->period_end->format('d/m/Y'),
            'when' => $report->generated_at?->locale('pt_BR')->translatedFormat('d/m/Y H:i') ?? 'sem data de geração',
            'itemsLabel' => $items === 1 ? '1 item' : $items.' itens',
            'status' => match ($report->status) {
                BaptismListReport::STATUS_READY => 'Aprovada',
                BaptismListReport::STATUS_ARCHIVED => 'Arquivada',
                default => 'Para aprovação',
            },
            'tone' => match ($report->status) {
                BaptismListReport::STATUS_READY => 'success',
                BaptismListReport::STATUS_ARCHIVED => 'neutral',
                default => 'warning',
            },
            'href' => route('secretaria.baptism-list'),
        ];
    }

    /**
     * @return list<array{label: string, href: string}>
     */
    private function secretariaLinksFor(User $user): array
    {
        $links = [];

        if ($user->isSuperAdmin() || $user->can('visualizar_relatorios')) {
            $links[] = ['label' => 'Lista de batismos', 'href' => route('secretaria.baptism-list')];
            $links[] = ['label' => 'Marcações', 'href' => route('secretaria.markings')];
            $links[] = ['label' => 'Quem atende', 'href' => route('secretaria.quem-atende')];
        }

        if ($user->isSuperAdmin() || $user->can('editar_evento')) {
            $links[] = ['label' => 'Avisos do boletim', 'href' => route('secretaria.notices')];
            $links[] = ['label' => 'Alterações de culto', 'href' => route('secretaria.schedule-changes')];
            $links[] = ['label' => 'Coletas', 'href' => route('secretaria.collections')];
        }

        return $links;
    }
}
