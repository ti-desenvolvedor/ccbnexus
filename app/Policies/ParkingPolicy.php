<?php

namespace App\Policies;

use App\Models\Parking;
use App\Models\User;
use App\Services\OrganizationalLocationService;

class ParkingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->can('gerenciar_enderecos');
    }

    public function view(User $user, Parking $parking): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        $parking->loadMissing('location');

        if (! $parking->location) {
            return $user->isSuperAdmin();
        }

        return app(OrganizationalLocationService::class)->userMayAccessLocation($user, $parking->location);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Parking $parking): bool
    {
        return $this->view($user, $parking);
    }

    public function delete(User $user, Parking $parking): bool
    {
        return $this->update($user, $parking);
    }
}
