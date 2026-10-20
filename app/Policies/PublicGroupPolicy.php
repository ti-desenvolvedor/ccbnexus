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

    public function view(User $user, PublicGroup $publicGroup): bool
    {
        $publicGroup->loadMissing('regional');

        return $this->managesPublicAudienceCatalog($user)
            && $this->canAccessCatalogRegional($user, $publicGroup->regional);
    }

    public function create(User $user): bool
    {
        return $this->managesPublicAudienceCatalog($user);
    }

    public function update(User $user, PublicGroup $publicGroup): bool
    {
        return $this->view($user, $publicGroup);
    }

    public function delete(User $user, PublicGroup $publicGroup): bool
    {
        $publicGroup->loadMissing('regional');

        return $this->canDeletePublicAudienceCatalog($user)
            && $this->canAccessCatalogRegional($user, $publicGroup->regional);
    }
}
