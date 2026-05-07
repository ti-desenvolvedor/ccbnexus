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

        if ($user->isSuperAdmin()) {
            return true;
        }

        $ids = app(OrganizationalLocationService::class)->visibleLocationIdsForUser($user);

        return $ids === null || in_array($location->id, $ids, true);
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
