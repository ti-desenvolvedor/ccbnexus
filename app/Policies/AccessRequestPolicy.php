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
        return $this->viewAny($user);
    }

    public function update(User $user, AccessRequest $accessRequest): bool
    {
        return $this->viewAny($user);
    }
}
