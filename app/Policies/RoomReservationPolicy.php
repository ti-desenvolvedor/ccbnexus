<?php

namespace App\Policies;

use App\Models\RoomReservation;
use App\Models\User;

class RoomReservationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->can('criar_reserva_sala')
            || $user->can('cancelar_reserva_sala')
            || $user->can('aprovar_reserva_sala');
    }

    public function view(User $user, RoomReservation $reservation): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        $reservation->loadMissing('meetingRoom');

        return $user->canAccessMeetingRoom($reservation->meetingRoom);
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->can('criar_reserva_sala');
    }

    public function update(User $user, RoomReservation $reservation): bool
    {
        return $this->view($user, $reservation)
            && ($user->isSuperAdmin()
                || $user->can('criar_reserva_sala')
                || $user->can('aprovar_reserva_sala'));
    }

    public function delete(User $user, RoomReservation $reservation): bool
    {
        return $this->view($user, $reservation)
            && ($user->isSuperAdmin() || $user->can('cancelar_reserva_sala'));
    }

    public function approve(User $user, RoomReservation $reservation): bool
    {
        return $this->view($user, $reservation)
            && ($user->isSuperAdmin() || $user->can('aprovar_reserva_sala'));
    }
}
