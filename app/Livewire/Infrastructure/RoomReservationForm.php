<?php

namespace App\Livewire\Infrastructure;

use App\Models\MeetingRoom;
use App\Models\RoomReservation;
use App\Services\RoomReservationService;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class RoomReservationForm extends Component
{
    use AuthorizesRequests;

    public int $meeting_room_id = 0;

    public string $title = '';

    public string $notes = '';

    public string $starts_at = '';

    public string $ends_at = '';

    public bool $requires_approval = false;

    public function mount(): void
    {
        $this->authorize('create', RoomReservation::class);
        $user = auth()->user();
        $room = MeetingRoom::query()->visibleToUser($user)->orderBy('name')->first();
        $this->meeting_room_id = (int) ($room?->id ?? 0);
        $this->starts_at = now()->addDay()->setHour(9)->setMinute(0)->format('Y-m-d\TH:i');
        $this->ends_at = now()->addDay()->setHour(10)->setMinute(0)->format('Y-m-d\TH:i');
    }

    public function save(RoomReservationService $reservations): void
    {
        $this->authorize('create', RoomReservation::class);

        $data = $this->validate([
            'meeting_room_id' => ['required', 'exists:meeting_rooms,id'],
            'title' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'requires_approval' => ['boolean'],
        ]);

        $room = MeetingRoom::query()->findOrFail($data['meeting_room_id']);
        $user = auth()->user();
        if (! $user->canAccessMeetingRoom($room)) {
            $this->addError('meeting_room_id', __('Sem permissão para reservar esta sala.'));

            return;
        }

        $starts = Carbon::parse($data['starts_at']);
        $ends = Carbon::parse($data['ends_at']);

        if ($reservations->hasConflict($room->id, $starts, $ends)) {
            $this->addError('starts_at', __('Já existe reserva aprovada ou pendente neste intervalo.'));

            return;
        }

        $status = $data['requires_approval'] ? 'pending' : 'approved';

        RoomReservation::query()->create([
            'meeting_room_id' => $room->id,
            'title' => $data['title'],
            'notes' => $data['notes'] ?? null,
            'starts_at' => $starts,
            'ends_at' => $ends,
            'status' => $status,
            'requires_approval' => $data['requires_approval'],
            'requested_by_user_id' => $user->id,
            'approved_by_user_id' => $status === 'approved' ? $user->id : null,
            'approved_at' => $status === 'approved' ? now() : null,
        ]);

        session()->flash('status', __('Reserva registada.'));

        $this->redirect(route('infrastructure.room-reservations.index'), navigate: true);
    }

    public function render()
    {
        $user = auth()->user();
        $rooms = MeetingRoom::query()->visibleToUser($user)->where('is_active', true)->orderBy('name')->get();

        return view('livewire.infrastructure.room-reservation-form', [
            'meetingRooms' => $rooms,
        ]);
    }
}
