<?php

namespace App\Services;

use App\Models\MeetingRoom;
use App\Models\RoomReservation;
use App\Models\User;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RoomReservationService
{
    /**
     * Verifica sobreposição com reservas ativas (não canceladas / rejeitadas),
     * incluindo janelas de setup/teardown.
     */
    public function hasConflict(
        int $meetingRoomId,
        DateTimeInterface $startsAt,
        DateTimeInterface $endsAt,
        ?int $exceptReservationId = null,
        int $setupMinutes = 0,
        int $teardownMinutes = 0
    ): bool {
        if ($startsAt >= $endsAt) {
            return true;
        }

        $windowStart = Carbon::parse($startsAt)->subMinutes(max(0, $setupMinutes));
        $windowEnd = Carbon::parse($endsAt)->addMinutes(max(0, $teardownMinutes));

        $query = RoomReservation::query()
            ->where('meeting_room_id', $meetingRoomId)
            ->whereNotIn('status', ['cancelled', 'rejected', 'draft']);

        if ($exceptReservationId) {
            $query->where('id', '!=', $exceptReservationId);
        }

        foreach ($query->get(['id', 'starts_at', 'ends_at', 'setup_minutes', 'teardown_minutes']) as $existing) {
            $existingStart = Carbon::parse($existing->starts_at)->subMinutes((int) ($existing->setup_minutes ?? 0));
            $existingEnd = Carbon::parse($existing->ends_at)->addMinutes((int) ($existing->teardown_minutes ?? 0));

            if ($existingStart < $windowEnd && $existingEnd > $windowStart) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array{meeting_room_id:int,title:string,notes?:?string,starts_at:DateTimeInterface,ends_at:DateTimeInterface,requires_approval?:bool,setup_minutes?:int,teardown_minutes?:int}  $data
     */
    public function create(array $data, User $user): RoomReservation
    {
        return DB::transaction(function () use ($data, $user) {
            $room = MeetingRoom::query()->whereKey($data['meeting_room_id'])->lockForUpdate()->firstOrFail();

            if (! $user->canAccessMeetingRoom($room)) {
                throw new InvalidArgumentException(__('Sem permissão para reservar esta sala.'));
            }

            $starts = $data['starts_at'];
            $ends = $data['ends_at'];
            $setup = (int) ($data['setup_minutes'] ?? 0);
            $teardown = (int) ($data['teardown_minutes'] ?? 0);

            if ($this->hasConflict($room->id, $starts, $ends, null, $setup, $teardown)) {
                throw new InvalidArgumentException(__('Já existe reserva aprovada ou pendente neste intervalo.'));
            }

            $canSelfApprove = $user->isSuperAdmin() || $user->can('aprovar_reserva_sala');
            $wantsApproval = (bool) ($data['requires_approval'] ?? false);

            if ($canSelfApprove && ! $wantsApproval) {
                $status = 'approved';
                $requiresApproval = false;
            } else {
                $status = 'pending';
                $requiresApproval = true;
            }

            return RoomReservation::query()->create([
                'meeting_room_id' => $room->id,
                'title' => $data['title'],
                'notes' => $data['notes'] ?? null,
                'starts_at' => $starts,
                'ends_at' => $ends,
                'setup_minutes' => $setup,
                'teardown_minutes' => $teardown,
                'status' => $status,
                'requires_approval' => $requiresApproval,
                'requested_by_user_id' => $user->id,
                'approved_by_user_id' => $status === 'approved' ? $user->id : null,
                'approved_at' => $status === 'approved' ? now() : null,
            ]);
        });
    }

    public function approve(RoomReservation $reservation, User $user, ?string $note = null): RoomReservation
    {
        return DB::transaction(function () use ($reservation, $user, $note) {
            MeetingRoom::query()->whereKey($reservation->meeting_room_id)->lockForUpdate()->firstOrFail();
            $locked = RoomReservation::query()->whereKey($reservation->id)->lockForUpdate()->firstOrFail();

            if ($this->hasConflict(
                $locked->meeting_room_id,
                $locked->starts_at,
                $locked->ends_at,
                $locked->id,
                (int) ($locked->setup_minutes ?? 0),
                (int) ($locked->teardown_minutes ?? 0)
            )) {
                throw new InvalidArgumentException(__('Conflito de horário: não é possível aprovar.'));
            }

            $locked->update([
                'status' => 'approved',
                'approved_by_user_id' => $user->id,
                'approved_at' => now(),
                'moderator_note' => $note,
            ]);

            return $locked->fresh();
        });
    }

    public function reject(RoomReservation $reservation, User $user, ?string $note = null): RoomReservation
    {
        $reservation->update([
            'status' => 'rejected',
            'approved_by_user_id' => $user->id,
            'approved_at' => now(),
            'moderator_note' => $note,
        ]);

        return $reservation->fresh();
    }
}
