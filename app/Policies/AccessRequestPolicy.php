<?php

namespace App\Policies;

use App\Models\AccessRequest;
use App\Models\User;

class AccessRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->can('aprovar_acesso');
    }

    public function view(User $user, AccessRequest $accessRequest): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($accessRequest->regional_id === null) {
            return false;
        }

        return in_array((int) $accessRequest->regional_id, $user->accessibleRegionalIds(), true);
    }

    public function update(User $user, AccessRequest $accessRequest): bool
    {
        return $this->view($user, $accessRequest);
    }
}
