<?php

namespace App\Livewire\Dashboard;

use App\Models\AccessRequest;
use App\Models\Administration;
use App\Models\Event;
use App\Models\PrayerHouse;
use App\Models\Regional;
use App\Models\RoomReservation;
use Livewire\Component;

class Metrics extends Component
{
    public int $regionals = 0;

    public int $administrations = 0;

    public int $prayer_houses = 0;

    public int $upcoming_events = 0;

    public int $pending_reservations = 0;

    public ?int $pending_access_requests = null;

    public function refresh(): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $rids = $user->accessibleRegionalIds();

        if ($user->isSuperAdmin()) {
            $this->regionals = Regional::query()->where('is_active', true)->count();
            $this->administrations = Administration::query()->count();
            $this->prayer_houses = PrayerHouse::query()->count();
            $regionalIdsForEvents = Regional::query()->pluck('id')->all();
        } else {
            if ($rids === []) {
                $this->regionals = 0;
                $this->administrations = 0;
                $this->prayer_houses = 0;
                $regionalIdsForEvents = [];
            } else {
                $this->regionals = Regional::query()->whereIn('id', $rids)->where('is_active', true)->count();
                $this->administrations = Administration::query()->whereIn('regional_id', $rids)->count();
                $this->prayer_houses = PrayerHouse::query()
                    ->whereHas('administration', fn ($q) => $q->whereIn('regional_id', $rids))
                    ->count();
                $regionalIdsForEvents = $rids;
            }
        }

        if ($regionalIdsForEvents === []) {
            $this->upcoming_events = 0;
        } else {
            $this->upcoming_events = Event::query()
                ->whereIn('regional_id', $regionalIdsForEvents)
                ->where('starts_at', '>=', now())
                ->where('starts_at', '<=', now()->addDays(30))
                ->where('status', '!=', 'cancelled')
                ->where('is_occurrence', false)
                ->count();
        }

        $this->pending_reservations = RoomReservation::query()
            ->where('status', 'pending')
            ->whereHas('meetingRoom', fn ($q) => $q->visibleToUser($user))
            ->count();

        $this->pending_access_requests = $user->can('aprovar_acesso')
            ? AccessRequest::query()->where('status', 'pending')->count()
            : null;
    }

    public function render()
    {
        return view('livewire.dashboard.metrics');
    }
}
