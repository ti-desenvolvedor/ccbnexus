<?php

namespace App\Livewire\Infrastructure;

use App\Models\Administration;
use App\Models\MeetingRoom;
use App\Models\PrayerHouse;
use App\Services\OrganizationalLocationService;
use App\Services\SlugService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Livewire\Component;

class MeetingRoomForm extends Component
{
    use AuthorizesRequests;

    public ?MeetingRoom $meetingRoom = null;

    public string $owner_type = Administration::class;

    public int $owner_id = 0;

    public string $name = '';

    public ?int $capacity = null;

    public bool $is_active = true;

    public ?int $location_id = null;

    public function mount(): void
    {
        if ($this->meetingRoom) {
            $this->authorize('update', $this->meetingRoom);
            $this->owner_type = $this->meetingRoom->owner_type;
            $this->owner_id = (int) $this->meetingRoom->owner_id;
            $this->name = $this->meetingRoom->name;
            $this->capacity = $this->meetingRoom->capacity;
            $this->is_active = $this->meetingRoom->is_active;
            $this->location_id = $this->meetingRoom->location_id;
        } else {
            $this->authorize('create', MeetingRoom::class);
            $user = auth()->user();
            $admin = Administration::query()
                ->when(! $user->isSuperAdmin(), fn ($q) => $q->whereIn('regional_id', $user->accessibleRegionalIds()))
                ->orderBy('name')
                ->first();
            if ($admin) {
                $this->owner_type = Administration::class;
                $this->owner_id = $admin->id;
            } else {
                $house = PrayerHouse::query()
                    ->when(! $user->isSuperAdmin(), function ($q) use ($user) {
                        $q->whereHas('administration', fn ($a) => $a->whereIn('regional_id', $user->accessibleRegionalIds()));
                    })
                    ->orderBy('name')
                    ->first();
                if ($house) {
                    $this->owner_type = PrayerHouse::class;
                    $this->owner_id = $house->id;
                }
            }
        }
    }

    public function updatedOwnerType(): void
    {
        $this->owner_id = 0;
        $this->location_id = null;
    }

    public function updatedOwnerId(): void
    {
        $allowed = app(OrganizationalLocationService::class)
            ->selectableIdsForMeetingRoomOwner($this->owner_type, $this->owner_id);
        if ($this->location_id && ! in_array((int) $this->location_id, $allowed, true)) {
            $this->location_id = null;
        }
    }

    public function save(OrganizationalLocationService $locationScope, SlugService $slugs): void
    {
        if ($this->meetingRoom) {
            $this->authorize('update', $this->meetingRoom);
        } else {
            $this->authorize('create', MeetingRoom::class);
        }

        $allowedTypes = [Administration::class, PrayerHouse::class];
        if (! in_array($this->owner_type, $allowedTypes, true)) {
            $this->addError('owner_type', __('Tipo de dono inválido.'));

            return;
        }

        $owner = $this->owner_type::query()->find($this->owner_id);
        if (! $owner) {
            $this->addError('owner_id', __('Dono não encontrado.'));

            return;
        }

        $user = auth()->user();
        if ($owner instanceof Administration && ! $user->isSuperAdmin() && ! $user->canAccessAdministration($owner)) {
            $this->addError('owner_id', __('Sem permissão para esta administração.'));

            return;
        }
        if ($owner instanceof PrayerHouse && ! $user->isSuperAdmin() && ! $user->canAccessPrayerHouse($owner)) {
            $this->addError('owner_id', __('Sem permissão para esta casa de oração.'));

            return;
        }

        $data = $this->validate([
            'owner_type' => ['required', 'string', 'max:255'],
            'owner_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'is_active' => ['boolean'],
            'location_id' => ['nullable', 'exists:locations,id'],
        ]);

        $data['slug'] = $this->meetingRoom?->slug ?? $slugs->generate(
            $data['name'],
            'meeting_rooms',
            ['owner_type' => $data['owner_type'], 'owner_id' => (int) $data['owner_id']],
            $this->meetingRoom?->id
        );

        $allowed = $locationScope->mergeCurrentLocation(
            $locationScope->selectableIdsForMeetingRoomOwner($data['owner_type'], (int) $data['owner_id']),
            $this->meetingRoom?->location_id
        );
        if (($data['location_id'] ?? null) && ! in_array((int) $data['location_id'], $allowed, true)) {
            $this->addError('location_id', __('Este local não pertence ao dono desta sala.'));

            return;
        }

        if ($this->meetingRoom) {
            $this->meetingRoom->update($data);
            session()->flash('status', __('Sala atualizada.'));
        } else {
            MeetingRoom::query()->create($data);
            session()->flash('status', __('Sala criada.'));
        }

        $this->redirect(route('infrastructure.meeting-rooms.index'), navigate: true);
    }

    protected function locationOptions(OrganizationalLocationService $locationScope): Collection
    {
        if ($this->owner_id <= 0) {
            return collect();
        }

        $ids = $locationScope->mergeCurrentLocation(
            $locationScope->selectableIdsForMeetingRoomOwner($this->owner_type, $this->owner_id),
            $this->location_id
        );

        return $locationScope->orderedLocationsQuery($ids)->limit(500)->get();
    }

    public function render(OrganizationalLocationService $locationScope)
    {
        $user = auth()->user();
        $administrations = Administration::query()
            ->when(! $user->isSuperAdmin(), fn ($q) => $q->whereIn('regional_id', $user->accessibleRegionalIds()))
            ->orderBy('name')
            ->limit(300)
            ->get();
        $prayerHouses = PrayerHouse::query()
            ->when(! $user->isSuperAdmin(), function ($q) use ($user) {
                $q->whereHas('administration', fn ($a) => $a->whereIn('regional_id', $user->accessibleRegionalIds()));
            })
            ->orderBy('name')
            ->limit(400)
            ->get();

        return view('livewire.infrastructure.meeting-room-form', [
            'administrations' => $administrations,
            'prayerHouses' => $prayerHouses,
            'locations' => $this->locationOptions($locationScope),
        ]);
    }
}
