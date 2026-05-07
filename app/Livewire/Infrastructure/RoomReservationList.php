<?php

namespace App\Livewire\Infrastructure;

use App\Models\RoomReservation;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class RoomReservationList extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public function approve(int $id): void
    {
        $reservation = RoomReservation::query()->findOrFail($id);
        $this->authorize('approve', $reservation);
        $reservation->update([
            'status' => 'approved',
            'approved_by_user_id' => auth()->id(),
            'approved_at' => now(),
        ]);
    }

    public function reject(int $id): void
    {
        $reservation = RoomReservation::query()->findOrFail($id);
        $this->authorize('approve', $reservation);
        $reservation->update([
            'status' => 'rejected',
            'approved_by_user_id' => auth()->id(),
            'approved_at' => now(),
        ]);
    }

    public function cancel(int $id): void
    {
        $reservation = RoomReservation::query()->findOrFail($id);
        $this->authorize('delete', $reservation);
        $reservation->update(['status' => 'cancelled']);
    }

    public function render()
    {
        $this->authorize('viewAny', RoomReservation::class);

        $user = auth()->user();
        $query = RoomReservation::query()
            ->with(['meetingRoom.owner', 'requestedBy'])
            ->orderByDesc('starts_at');

        $query->whereHas('meetingRoom', fn ($q) => $q->visibleToUser($user));

        return view('livewire.infrastructure.room-reservation-list', [
            'reservations' => $query->paginate(15),
        ]);
    }
}
