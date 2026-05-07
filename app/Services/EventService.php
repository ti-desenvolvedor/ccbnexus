<?php

namespace App\Services;

use App\Models\Approval;
use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EventService
{
    public function __construct(
        protected EventRecurrenceService $recurrence,
    ) {}

    public function store(array $data, User $user): Event
    {
        return DB::transaction(function () use ($data, $user) {
            $status = $data['status'] ?? ($user->can('aprovar_evento') ? 'published' : 'draft');
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
        return DB::transaction(function () use ($event, $data) {
            $payload = collect($data)->except([
                'audience_ids', 'public_position_ids', 'approval_note',
                'recurrence_monthly_nth', 'recurrence_monthly_weekday', 'recurrence_months_filter', 'recurrence_months_list',
                'recurrence_interval_years',
            ])->all();
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
}
