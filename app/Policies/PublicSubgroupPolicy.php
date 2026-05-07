<?php

namespace App\Policies;

use App\Models\PublicSubgroup;
use App\Models\User;
use App\Policies\Concerns\ManagesPublicAudienceCatalog;

class PublicSubgroupPolicy
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

    public function update(User $user, PublicSubgroup $publicSubgroup): bool
    {
        return $this->managesPublicAudienceCatalog($user);
    }

    public function delete(User $user, PublicSubgroup $publicSubgroup): bool
    {
        return $this->managesPublicAudienceCatalog($user);
    }
}
