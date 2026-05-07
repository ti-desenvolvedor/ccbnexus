<?php

namespace App\Policies;

use App\Models\Administration;
use App\Models\User;

class AdministrationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->can('gerenciar_administracoes');
    }

    public function view(User $user, Administration $administration): bool
    {
        return $this->viewAny($user) && $user->canAccessAdministration($administration);
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->can('gerenciar_administracoes');
    }

    public function update(User $user, Administration $administration): bool
    {
        return $this->create($user) && $user->canAccessAdministration($administration);
    }

    public function delete(User $user, Administration $administration): bool
    {
        return $this->update($user, $administration);
    }
}
