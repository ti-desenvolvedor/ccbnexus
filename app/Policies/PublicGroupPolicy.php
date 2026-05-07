<?php

namespace App\Policies;

use App\Models\PublicGroup;
use App\Models\User;
use App\Policies\Concerns\ManagesPublicAudienceCatalog;

class PublicGroupPolicy
{
    use ManagesPublicAudienceCatalog;

    public function viewAny(User $user): bool
    {
        return $this->managesPublicAudienceCatalog($user);
    }

    public function create(User $user): bool
    {
        return $this->managesPublicAudienceCatalog($user);
    }

    public function update(User $user, PublicGroup $publicGroup): bool
    {
        return $this->managesPublicAudienceCatalog($user);
    }

    public function delete(User $user, PublicGroup $publicGroup): bool
    {
        return $this->managesPublicAudienceCatalog($user);
    }
}
