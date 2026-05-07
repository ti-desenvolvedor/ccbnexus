<?php

namespace App\Policies;

use App\Models\Regional;
use App\Models\User;

class RegionalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->can('gerenciar_regionais');
    }

    public function view(User $user, Regional $regional): bool
    {
        return $this->viewAny($user) && $user->canAccessRegional($regional);
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->can('gerenciar_regionais');
    }

    public function update(User $user, Regional $regional): bool
    {
        return $this->create($user) && $user->canAccessRegional($regional);
    }

    public function delete(User $user, Regional $regional): bool
    {
        return $this->update($user, $regional);
    }
}
