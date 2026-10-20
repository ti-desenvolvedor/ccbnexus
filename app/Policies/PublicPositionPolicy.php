<?php

namespace App\Policies;

use App\Models\PublicPosition;
use App\Models\Regional;
use App\Models\User;
use App\Policies\Concerns\ManagesPublicAudienceCatalog;

class PublicPositionPolicy
{
    use ManagesPublicAudienceCatalog;

    public function viewAny(User $user): bool
    {
        return $this->managesPublicAudienceCatalog($user);
    }

    public function view(User $user, PublicPosition $publicPosition): bool
    {
        return $this->managesPublicAudienceCatalog($user)
            && $this->canAccessCatalogRegional($user, $this->resolvePositionRegional($publicPosition));
    }

    public function create(User $user): bool
    {
        return $this->managesPublicAudienceCatalog($user);
    }

    public function update(User $user, PublicPosition $publicPosition): bool
    {
        return $this->view($user, $publicPosition);
    }

    public function delete(User $user, PublicPosition $publicPosition): bool
    {
        return $this->canDeletePublicAudienceCatalog($user)
            && $this->canAccessCatalogRegional($user, $this->resolvePositionRegional($publicPosition));
    }

    protected function resolvePositionRegional(PublicPosition $publicPosition): ?Regional
    {
        $publicPosition->loadMissing([
            'department.regional',
            'department.administration.regional',
            'department.prayerHouse.administration.regional',
            'subgroup.group.regional',
        ]);

        $department = $publicPosition->department;
        if ($department) {
            return $department->regional
                ?? $department->administration?->regional
                ?? $department->prayerHouse?->administration?->regional;
        }

        return $publicPosition->subgroup?->group?->regional;
    }
}
