<?php

namespace App\Services;

use App\Models\RoomReservation;
use DateTimeInterface;

class RoomReservationService
{
    /**
     * Verifica sobreposição com reservas ativas (não canceladas / rejeitadas).
     */
    public function hasConflict(
        int $meetingRoomId,
        DateTimeInterface $startsAt,
        DateTimeInterface $endsAt,
        ?int $exceptReservationId = null
    ): bool {
        if ($startsAt >= $endsAt) {
            return true;
        }

        $query = RoomReservation::query()
            ->where('meeting_room_id', $meetingRoomId)
            ->whereNotIn('status', ['cancelled', 'rejected', 'draft'])
            ->where(function ($q) use ($startsAt, $endsAt) {
                $q->whereBetween('starts_at', [$startsAt, $endsAt])
                    ->orWhereBetween('ends_at', [$startsAt, $endsAt])
                    ->orWhere(function ($inner) use ($startsAt, $endsAt) {
                        $inner->where('starts_at', '<=', $startsAt)
                            ->where('ends_at', '>=', $endsAt);
                    });
            });

        if ($exceptReservationId) {
            $query->where('id', '!=', $exceptReservationId);
        }

        return $query->exists();
    }
}
