<?php

namespace App\Services;

use App\Models\Approval;
use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EventService
{
    public function __construct(
        protected EventRecurrenceService $recurrence,
        protected OrganizationalContextService $context,
    ) {}

    public function store(array $data, User $user): Event
    {
        return DB::transaction(function () use ($data, $user) {
            $data = $this->ensureRegionalOnCreate($data, $user);

            $status = $this->resolveWritableStatus($data['status'] ?? null, $user);
            $payload = collect($data)->except([
                'audience_ids', 'public_position_ids', 'approval_note',
                'recurrence_monthly_nth', 'recurrence_monthly_weekday', 'recurrence_months_filter', 'recurrence_months_list',
                'recurrence_interval_years',
            ])->all();

            $event = Event::query()->create(array_merge($payload, [
                'created_by' => $user->id,
                'status' => $status,
            ]));

            if ($status === 'pending_approval') {
                $this->createPendingApproval($event, $user, $data['approval_note'] ?? null);
            }

            if (array_key_exists('audience_ids', $data)) {
                $event->audiences()->sync(
                    collect($data['audience_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->all()
                );
            }

            if (array_key_exists('public_position_ids', $data)) {
                $event->publicPositions()->sync(
                    collect($data['public_position_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->all()
                );
            }

            $this->recurrence->syncWeeklyOccurrences($event);

            return $event->fresh(['audiences', 'publicPositions']);
        });
    }

    public function update(Event $event, array $data, User $user): Event
    {
        return DB::transaction(function () use ($event, $data, $user) {
            $payload = collect($data)->except([
                'audience_ids', 'public_position_ids', 'approval_note',
                'recurrence_monthly_nth', 'recurrence_monthly_weekday', 'recurrence_months_filter', 'recurrence_months_list',
                'recurrence_interval_years',
            ])->all();

            if (array_key_exists('status', $payload)) {
                $payload['status'] = $this->resolveWritableStatus($payload['status'], $user, $event->status);
            }

            $event->update($payload);
            if (array_key_exists('audience_ids', $data)) {
                $event->audiences()->sync(
                    collect($data['audience_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->all()
                );
            }
            if (array_key_exists('public_position_ids', $data)) {
                $event->publicPositions()->sync(
                    collect($data['public_position_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->all()
                );
            }
            $this->recurrence->syncWeeklyOccurrences($event);

            return $event->fresh(['audiences', 'publicPositions']);
        });
    }

    public function cancel(Event $event, User $user, string $reason): void
    {
        if (! $user->can('cancelar_evento', $event)) {
            abort(403);
        }
        $event->update([
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
            'cancelled_at' => Carbon::now(),
            'cancelled_by' => $user->id,
        ]);
    }

    public function submitForApproval(Event $event, User $user, ?string $note = null): void
    {
        $event->update(['status' => 'pending_approval']);
        $this->createPendingApproval($event, $user, $note);
    }

    public function approve(Event $event, User $user, ?string $note = null): void
    {
        $event->update(['status' => 'published']);
        $event->approvals()->where('status', 'pending')->update([
            'status' => 'approved',
            'decided_by_user_id' => $user->id,
            'decided_at' => now(),
            'decision_note' => $note,
        ]);
    }

    public function reject(Event $event, User $user, ?string $note = null): void
    {
        $event->update(['status' => 'rejected']);
        $event->approvals()->where('status', 'pending')->update([
            'status' => 'rejected',
            'decided_by_user_id' => $user->id,
            'decided_at' => now(),
            'decision_note' => $note,
        ]);
    }

    protected function createPendingApproval(Event $event, User $user, ?string $note = null): void
    {
        Approval::query()->create([
            'approvable_type' => Event::class,
            'approvable_id' => $event->id,
            'status' => 'pending',
            'requested_by_user_id' => $user->id,
            'request_note' => $note,
        ]);
    }

    /**
     * Eventos sem regional só para super-admin; restantes herdam contexto ativo / vínculo do utilizador.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function ensureRegionalOnCreate(array $data, User $user): array
    {
        $regionalId = $data['regional_id'] ?? null;
        if ($regionalId !== null && $regionalId !== '' && (int) $regionalId > 0) {
            $data['regional_id'] = (int) $regionalId;

            return $data;
        }

        if ($user->isSuperAdmin()) {
            $data['regional_id'] = null;

            return $data;
        }

        $fallback = $this->context->activeRegionalId() ?? $user->organizationalRegionalId();
        if ($fallback === null) {
            throw ValidationException::withMessages([
                'regional_id' => [__('Selecione uma regional para o evento.')],
            ]);
        }

        $data['regional_id'] = $fallback;

        return $data;
    }

    /**
     * published só com aprovar_evento; restantes estados permitidos sem elevação.
     */
    protected function resolveWritableStatus(?string $requested, User $user, ?string $current = null): string
    {
        $requested = $requested ?: ($current ?: 'draft');
        $canPublish = $user->isSuperAdmin() || $user->can('aprovar_evento');

        if ($requested === 'published' && ! $canPublish) {
            return 'pending_approval';
        }

        if (! in_array($requested, ['draft', 'pending_approval', 'published', 'cancelled', 'rejected'], true)) {
            return 'draft';
        }

        return $requested;
    }
}
