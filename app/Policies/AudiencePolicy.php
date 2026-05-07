<?php

namespace App\Policies;

use App\Models\Audience;
use App\Models\User;

class AudiencePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->can('gerenciar_avisos');
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Audience $audience): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, Audience $audience): bool
    {
        return $this->viewAny($user);
    }
}
