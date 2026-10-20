<?php

namespace App\Policies;

use App\Models\Audience;
use App\Models\User;
use App\Policies\Concerns\ManagesPublicAudienceCatalog;

class AudiencePolicy
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

    public function update(User $user, Audience $audience): bool
    {
        return $this->managesPublicAudienceCatalog($user);
    }

    public function delete(User $user, Audience $audience): bool
    {
        return $this->canDeletePublicAudienceCatalog($user);
    }
}
