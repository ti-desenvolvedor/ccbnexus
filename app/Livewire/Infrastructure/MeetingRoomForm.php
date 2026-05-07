<?php

namespace App\Livewire\Infrastructure;

use App\Models\Administration;
use App\Models\Location;
use App\Models\MeetingRoom;
use App\Models\PrayerHouse;
use App\Services\OrganizationalLocationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;

class MeetingRoomForm extends Component
{
    use AuthorizesRequests;

    public ?MeetingRoom $meetingRoom = null;

    public string $owner_type = Administration::class;

    public int $owner_id = 0;

    public string $name = '';

    public string $slug = '';

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
            $this->slug = $this->meetingRoom->slug;
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

    public function save(OrganizationalLocationService $locationScope): void
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

        $slugRule = Rule::unique('meeting_rooms', 'slug')
            ->where('owner_type', $this->owner_type)
            ->where('owner_id', $this->owner_id);
        if ($this->meetingRoom) {
            $slugRule->ignore($this->meetingRoom->id);
        }

        $data = $this->validate([
            'owner_type' => ['required', 'string', 'max:255'],
            'owner_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slugRule],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'is_active' => ['boolean'],
            'location_id' => ['nullable', 'exists:locations,id'],
        ]);

        if (! $user->isSuperAdmin() && ($data['location_id'] ?? null)) {
            $allowed = $locationScope->mergeCurrentLocation(
                $locationScope->selectableIdsForMeetingRoomOwner($data['owner_type'], (int) $data['owner_id']),
                $this->meetingRoom?->location_id
            );
            if (! in_array((int) $data['location_id'], $allowed, true)) {
                $this->addError('location_id', __('Este local não pertence ao dono desta sala.'));

                return;
            }
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

        if ($user->isSuperAdmin()) {
            $locations = Location::query()->orderBy('name')->limit(500)->get();
        } else {
            $allowed = $locationScope->selectableIdsForMeetingRoomOwner($this->owner_type, $this->owner_id);
            $ids = $locationScope->mergeCurrentLocation($allowed, $this->location_id);
            $locations = $locationScope->orderedLocationsQuery($ids)->limit(500)->get();
        }

        return view('livewire.infrastructure.meeting-room-form', [
            'administrations' => $administrations,
            'prayerHouses' => $prayerHouses,
            'locations' => $locations,
        ]);
    }
}
