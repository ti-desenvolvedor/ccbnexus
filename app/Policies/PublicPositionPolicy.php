<?php

namespace App\Policies;

use App\Models\PublicPosition;
use App\Models\User;
use App\Policies\Concerns\ManagesPublicAudienceCatalog;

class PublicPositionPolicy
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

    public function update(User $user, PublicPosition $publicPosition): bool
    {
        return $this->managesPublicAudienceCatalog($user);
    }

    public function delete(User $user, PublicPosition $publicPosition): bool
    {
        return $this->managesPublicAudienceCatalog($user);
    }
}
