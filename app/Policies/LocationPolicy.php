<?php

namespace App\Policies;

use App\Models\Location;
use App\Models\User;
use App\Services\OrganizationalLocationService;

class LocationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->can('gerenciar_enderecos');
    }

    public function view(User $user, Location $location): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        return app(OrganizationalLocationService::class)->userMayAccessLocation($user, $location);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Location $location): bool
    {
        return $this->view($user, $location);
    }

    public function delete(User $user, Location $location): bool
    {
        return $this->update($user, $location);
    }
}
