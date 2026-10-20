<?php

namespace App\Livewire\Infrastructure;

use App\Models\MeetingRoom;
use App\Models\RoomReservation;
use App\Services\RoomReservationService;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use InvalidArgumentException;
use Livewire\Component;

class RoomReservationForm extends Component
{
    use AuthorizesRequests;

    public int $meeting_room_id = 0;

    public string $title = '';

    public string $notes = '';

    public string $starts_at = '';

    public string $ends_at = '';

    /** Pedido explícito de fila de aprovação (ignorado se o user não pode auto-aprovar). */
    public bool $requires_approval = false;

    public int $setup_minutes = 0;

    public int $teardown_minutes = 0;

    public function mount(): void
    {
        $this->authorize('create', RoomReservation::class);
        $user = auth()->user();
        $prefRoom = (int) request()->query('meeting_room_id', 0);
        $room = $prefRoom
            ? MeetingRoom::query()->visibleToUser($user)->whereKey($prefRoom)->first()
            : MeetingRoom::query()->visibleToUser($user)->orderBy('name')->first();
        $this->meeting_room_id = (int) ($room?->id ?? 0);
        $this->starts_at = (string) (request()->query('starts_at') ?: now()->addDay()->setHour(9)->setMinute(0)->format('Y-m-d\TH:i'));
        $this->ends_at = (string) (request()->query('ends_at') ?: now()->addDay()->setHour(10)->setMinute(0)->format('Y-m-d\TH:i'));
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
            'setup_minutes' => ['integer', 'min:0', 'max:240'],
            'teardown_minutes' => ['integer', 'min:0', 'max:240'],
        ]);

        try {
            $reservations->create([
                'meeting_room_id' => (int) $data['meeting_room_id'],
                'title' => $data['title'],
                'notes' => $data['notes'] ?? null,
                'starts_at' => Carbon::parse($data['starts_at']),
                'ends_at' => Carbon::parse($data['ends_at']),
                'requires_approval' => (bool) $data['requires_approval'],
                'setup_minutes' => (int) $data['setup_minutes'],
                'teardown_minutes' => (int) $data['teardown_minutes'],
            ], auth()->user());
        } catch (InvalidArgumentException $e) {
            $this->addError('starts_at', $e->getMessage());

            return;
        }

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
