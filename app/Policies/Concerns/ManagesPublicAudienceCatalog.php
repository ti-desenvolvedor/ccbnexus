<?php

namespace App\Policies\Concerns;

use App\Models\User;

trait ManagesPublicAudienceCatalog
{
    protected function managesPublicAudienceCatalog(User $user): bool
    {
        return $user->isSuperAdmin() || $user->can('gerenciar_avisos');
    }
}
