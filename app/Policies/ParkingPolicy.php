<?php

namespace App\Policies;

use App\Models\Parking;
use App\Models\User;

class ParkingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->can('gerenciar_enderecos');
    }

    public function view(User $user, Parking $parking): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Parking $parking): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, Parking $parking): bool
    {
        return $this->viewAny($user);
    }
}
