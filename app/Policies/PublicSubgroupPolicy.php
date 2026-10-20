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

    public function view(User $user, PublicSubgroup $publicSubgroup): bool
    {
        $publicSubgroup->loadMissing('group.regional');

        return $this->managesPublicAudienceCatalog($user)
            && $this->canAccessCatalogRegional($user, $publicSubgroup->group?->regional);
    }

    public function create(User $user): bool
    {
        return $this->managesPublicAudienceCatalog($user);
    }

    public function update(User $user, PublicSubgroup $publicSubgroup): bool
    {
        return $this->view($user, $publicSubgroup);
    }

    public function delete(User $user, PublicSubgroup $publicSubgroup): bool
    {
        $publicSubgroup->loadMissing('group.regional');

        return $this->canDeletePublicAudienceCatalog($user)
            && $this->canAccessCatalogRegional($user, $publicSubgroup->group?->regional);
    }
}
