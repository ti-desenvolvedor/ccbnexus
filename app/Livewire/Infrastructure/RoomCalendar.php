<?php

namespace App\Livewire\Infrastructure;

use App\Models\MeetingRoom;
use App\Models\RoomReservation;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class RoomCalendar extends Component
{
    use AuthorizesRequests;

    public ?int $meeting_room_id = null;

    public string $week_start = '';

    public function mount(): void
    {
        $this->authorize('viewAny', RoomReservation::class);
        $user = auth()->user();
        $room = MeetingRoom::query()->visibleToUser($user)->where('is_active', true)->orderBy('name')->first();
        $this->meeting_room_id = $room?->id;
        $this->week_start = now()->startOfWeek()->toDateString();
    }

    public function previousWeek(): void
    {
        $this->week_start = Carbon::parse($this->week_start)->subWeek()->toDateString();
    }

    public function nextWeek(): void
    {
        $this->week_start = Carbon::parse($this->week_start)->addWeek()->toDateString();
    }

    public function bookSlot(string $day, int $hour): void
    {
        $this->authorize('create', RoomReservation::class);
        if (! $this->meeting_room_id) {
            return;
        }

        $starts = Carbon::parse($day)->setHour($hour)->setMinute(0);
        $ends = (clone $starts)->addHour();

        $this->redirect(route('infrastructure.room-reservations.create', [
            'meeting_room_id' => $this->meeting_room_id,
            'starts_at' => $starts->format('Y-m-d\TH:i'),
            'ends_at' => $ends->format('Y-m-d\TH:i'),
        ]), navigate: true);
    }

    public function render()
    {
        $user = auth()->user();
        $rooms = MeetingRoom::query()->visibleToUser($user)->where('is_active', true)->orderBy('name')->get();
        $start = Carbon::parse($this->week_start ?: now())->startOfWeek();
        $end = (clone $start)->endOfWeek();

        $days = collect(range(0, 6))->map(fn ($i) => (clone $start)->addDays($i));
        $hours = range(7, 21);

        $reservations = collect();
        if ($this->meeting_room_id) {
            $reservations = RoomReservation::query()
                ->where('meeting_room_id', $this->meeting_room_id)
                ->whereNotIn('status', ['cancelled', 'rejected', 'draft'])
                ->where('starts_at', '<', $end)
                ->where('ends_at', '>', $start)
                ->orderBy('starts_at')
                ->get();
        }

        $occupied = [];
        foreach ($reservations as $r) {
            $cursor = Carbon::parse($r->starts_at)->copy()->subMinutes((int) ($r->setup_minutes ?? 0));
            $until = Carbon::parse($r->ends_at)->copy()->addMinutes((int) ($r->teardown_minutes ?? 0));
            while ($cursor < $until) {
                $key = $cursor->format('Y-m-d').'|'.$cursor->hour;
                $occupied[$key] = $r->status;
                $cursor->addHour();
            }
        }

        return view('livewire.infrastructure.room-calendar', [
            'rooms' => $rooms,
            'days' => $days,
            'hours' => $hours,
            'occupied' => $occupied,
            'reservations' => $reservations,
            'weekLabel' => $start->format('d/m').' – '.$end->format('d/m/Y'),
        ]);
    }
}
