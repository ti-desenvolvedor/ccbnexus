<?php

namespace App\Services;

use App\Models\Administration;
use App\Models\MeetingRoom;
use App\Models\PrayerHouse;
use App\Models\Regional;
use App\Models\RoomAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class RoomAssignmentService
{
    /**
     * @param  class-string<Model>  $assignableType
     */
    public function attach(MeetingRoom $room, string $assignableType, int $assignableId, User $actor): RoomAssignment
    {
        $assignable = $assignableType::query()->findOrFail($assignableId);
        $this->assertAssignableInHierarchy($room, $assignable);
        $this->assertActorMayManageRoom($actor, $room);

        return RoomAssignment::query()->firstOrCreate(
            [
                'meeting_room_id' => $room->id,
                'assignable_type' => $assignable->getMorphClass(),
                'assignable_id' => $assignable->getKey(),
            ],
        );
    }

    public function detach(RoomAssignment $assignment, User $actor): void
    {
        $this->assertActorMayManageRoom($actor, $assignment->meetingRoom);
        $assignment->delete();
    }

    protected function assertActorMayManageRoom(User $actor, MeetingRoom $room): void
    {
        if (! $actor->can('update', $room)) {
            throw ValidationException::withMessages([
                'room' => __('Sem permissão para gerir atribuições desta sala.'),
            ]);
        }
    }

    protected function assertAssignableInHierarchy(MeetingRoom $room, Model $assignable): void
    {
        $room->loadMissing('owner');
        $owner = $room->owner;
        $regionalId = null;
        if ($owner instanceof Administration) {
            $regionalId = $owner->regional_id;
        }
        if ($owner instanceof PrayerHouse) {
            $regionalId = $owner->administration?->regional_id;
        }

        if ($regionalId === null) {
            throw ValidationException::withMessages([
                'assignable' => __('Sala sem dono válido para validar atribuição.'),
            ]);
        }

        if ($assignable instanceof Regional && (int) $assignable->id === (int) $regionalId) {
            return;
        }
        if ($assignable instanceof Administration && (int) $assignable->regional_id === (int) $regionalId) {
            return;
        }
        if ($assignable instanceof PrayerHouse && (int) $assignable->administration?->regional_id === (int) $regionalId) {
            return;
        }

        throw ValidationException::withMessages([
            'assignable' => __('A entidade atribuída tem de pertencer à mesma regional da sala.'),
        ]);
    }
}
