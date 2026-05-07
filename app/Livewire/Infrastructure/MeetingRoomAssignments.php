<?php

namespace App\Livewire\Infrastructure;

use App\Models\Administration;
use App\Models\MeetingRoom;
use App\Models\PrayerHouse;
use App\Models\Regional;
use App\Models\RoomAssignment;
use App\Services\RoomAssignmentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;

class MeetingRoomAssignments extends Component
{
    use AuthorizesRequests;

    public MeetingRoom $meetingRoom;

    public string $assignable_type = Administration::class;

    public int $assignable_id = 0;

    public function mount(MeetingRoom $meetingRoom): void
    {
        $this->meetingRoom = $meetingRoom;
        $this->authorize('update', $this->meetingRoom);
        $this->meetingRoom->load(['assignments.assignable', 'owner']);
        $this->pickDefaultAssignable();
    }

    protected function pickDefaultAssignable(): void
    {
        $owner = $this->meetingRoom->owner;
        if ($owner instanceof Administration) {
            $this->assignable_type = Administration::class;
            $this->assignable_id = $owner->id;
        } elseif ($owner instanceof PrayerHouse) {
            $this->assignable_type = PrayerHouse::class;
            $this->assignable_id = $owner->id;
        }
    }

    public function addAssignment(RoomAssignmentService $service): void
    {
        $this->authorize('update', $this->meetingRoom);
        $this->validate([
            'assignable_type' => ['required', Rule::in([Regional::class, Administration::class, PrayerHouse::class])],
            'assignable_id' => ['required', 'integer', 'min:1'],
        ]);
        $service->attach($this->meetingRoom, $this->assignable_type, $this->assignable_id, auth()->user());
        $this->meetingRoom->load(['assignments.assignable']);
        $this->dispatch('assignments-updated');
    }

    public function remove(int $assignmentId, RoomAssignmentService $service): void
    {
        $assignment = RoomAssignment::query()->where('meeting_room_id', $this->meetingRoom->id)->findOrFail($assignmentId);
        $this->authorize('update', $this->meetingRoom);
        $service->detach($assignment, auth()->user());
        $this->meetingRoom->load(['assignments.assignable']);
    }

    public function render()
    {
        $user = auth()->user();
        $regionals = Regional::query()
            ->when(! $user->isSuperAdmin(), fn ($q) => $q->whereIn('id', $user->accessibleRegionalIds()))
            ->orderBy('name')
            ->get();
        $administrations = Administration::query()
            ->when(! $user->isSuperAdmin(), fn ($q) => $q->whereIn('regional_id', $user->accessibleRegionalIds()))
            ->orderBy('name')
            ->limit(400)
            ->get();
        $houses = PrayerHouse::query()
            ->when(! $user->isSuperAdmin(), function ($q) use ($user) {
                $q->whereHas('administration', fn ($a) => $a->whereIn('regional_id', $user->accessibleRegionalIds()));
            })
            ->orderBy('name')
            ->limit(500)
            ->get();

        return view('livewire.infrastructure.meeting-room-assignments', [
            'regionals' => $regionals,
            'administrations' => $administrations,
            'houses' => $houses,
        ]);
    }
}
