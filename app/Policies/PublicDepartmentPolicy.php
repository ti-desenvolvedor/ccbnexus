<?php

namespace App\Policies;

use App\Models\PublicDepartment;
use App\Models\Regional;
use App\Models\User;
use App\Policies\Concerns\ManagesPublicAudienceCatalog;

class PublicDepartmentPolicy
{
    use ManagesPublicAudienceCatalog;

    public function viewAny(User $user): bool
    {
        return $this->managesPublicAudienceCatalog($user);
    }

    public function view(User $user, PublicDepartment $publicDepartment): bool
    {
        return $this->managesPublicAudienceCatalog($user)
            && $this->canAccessCatalogRegional($user, $this->resolveDepartmentRegional($publicDepartment));
    }

    public function create(User $user): bool
    {
        return $this->managesPublicAudienceCatalog($user);
    }

    public function update(User $user, PublicDepartment $publicDepartment): bool
    {
        return $this->view($user, $publicDepartment);
    }

    public function delete(User $user, PublicDepartment $publicDepartment): bool
    {
        return $this->canDeletePublicAudienceCatalog($user)
            && $this->canAccessCatalogRegional($user, $this->resolveDepartmentRegional($publicDepartment));
    }

    protected function resolveDepartmentRegional(PublicDepartment $publicDepartment): ?Regional
    {
        $publicDepartment->loadMissing([
            'regional',
            'administration.regional',
            'prayerHouse.administration.regional',
        ]);

        return $publicDepartment->regional
            ?? $publicDepartment->administration?->regional
            ?? $publicDepartment->prayerHouse?->administration?->regional;
    }
}
