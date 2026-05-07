<?php

namespace App\Policies;

use App\Models\MeetingRoom;
use App\Models\User;

class MeetingRoomPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->can('gerenciar_salas');
    }

    public function view(User $user, MeetingRoom $meetingRoom): bool
    {
        return $this->viewAny($user) && $user->canAccessMeetingRoom($meetingRoom);
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->can('gerenciar_salas');
    }

    public function update(User $user, MeetingRoom $meetingRoom): bool
    {
        return $this->create($user) && $user->canAccessMeetingRoom($meetingRoom);
    }

    public function delete(User $user, MeetingRoom $meetingRoom): bool
    {
        return $this->update($user, $meetingRoom);
    }
}
