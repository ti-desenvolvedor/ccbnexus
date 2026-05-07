<?php

namespace App\Livewire\Infrastructure;

use App\Models\MeetingRoom;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class MeetingRoomList extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $this->authorize('viewAny', MeetingRoom::class);

        $user = auth()->user();
        $query = MeetingRoom::query()->with(['owner', 'location'])->orderBy('name');
        $query->visibleToUser($user);

        if ($this->search !== '') {
            $s = '%'.$this->search.'%';
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', $s)->orWhere('slug', 'like', $s);
            });
        }

        return view('livewire.infrastructure.meeting-room-list', [
            'meetingRooms' => $query->paginate(12),
        ]);
    }
}
