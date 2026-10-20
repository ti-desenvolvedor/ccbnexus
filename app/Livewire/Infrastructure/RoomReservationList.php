<?php

namespace App\Livewire\Infrastructure;

use App\Livewire\Concerns\UsesAccountPagination;
use App\Models\RoomReservation;
use App\Services\RoomReservationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use InvalidArgumentException;
use Livewire\Component;
use Livewire\WithPagination;

class RoomReservationList extends Component
{
    use AuthorizesRequests;
    use UsesAccountPagination;
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public string $moderator_note = '';

    public function approve(int $id, RoomReservationService $reservations): void
    {
        $reservation = RoomReservation::query()->findOrFail($id);
        $this->authorize('approve', $reservation);

        $note = trim($this->moderator_note);
        if ($note === '') {
            $this->addError('moderator_note', __('Justificativa obrigatória para aprovar.'));

            return;
        }

        try {
            $reservations->approve($reservation, auth()->user(), $note);
        } catch (InvalidArgumentException $e) {
            $this->addError('moderator_note', $e->getMessage());

            return;
        }

        $this->moderator_note = '';
        session()->flash('status', __('Reserva aprovada.'));
    }

    public function reject(int $id, RoomReservationService $reservations): void
    {
        $reservation = RoomReservation::query()->findOrFail($id);
        $this->authorize('approve', $reservation);

        $note = trim($this->moderator_note);
        if ($note === '') {
            $this->addError('moderator_note', __('Justificativa obrigatória para rejeitar.'));

            return;
        }

        $reservations->reject($reservation, auth()->user(), $note);
        $this->moderator_note = '';
        session()->flash('status', __('Reserva rejeitada.'));
    }

    public function cancel(int $id): void
    {
        $reservation = RoomReservation::query()->findOrFail($id);
        $this->authorize('delete', $reservation);
        $reservation->update(['status' => 'cancelled']);
        session()->flash('status', __('Reserva cancelada.'));
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
            'reservations' => $query->paginate($this->paginatePerPage()),
        ]);
    }
}
