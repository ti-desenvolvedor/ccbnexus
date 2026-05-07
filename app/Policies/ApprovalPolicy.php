<?php

namespace App\Policies;

use App\Models\Approval;
use App\Models\User;

class ApprovalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->can('aprovar_evento');
    }

    public function update(User $user, Approval $approval): bool
    {
        return $this->viewAny($user);
    }
}
