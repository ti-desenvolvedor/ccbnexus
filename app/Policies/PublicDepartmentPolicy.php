<?php

namespace App\Policies;

use App\Models\PublicDepartment;
use App\Models\User;
use App\Policies\Concerns\ManagesPublicAudienceCatalog;

class PublicDepartmentPolicy
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

    public function update(User $user, PublicDepartment $publicDepartment): bool
    {
        return $this->managesPublicAudienceCatalog($user);
    }

    public function delete(User $user, PublicDepartment $publicDepartment): bool
    {
        return $this->managesPublicAudienceCatalog($user);
    }
}
