<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class UserManagementService
{
    public function listingQuery(User $viewer): Builder
    {
        $q = User::query()
            ->with(['roles', 'regional', 'administration', 'prayerHouse'])
            ->orderBy('name');

        if ($viewer->isSuperAdmin()) {
            return $q;
        }

        $ids = $viewer->accessibleRegionalIds();
        if ($ids === []) {
            return $q->whereRaw('0 = 1');
        }

        return $q
            ->where('is_super_admin', false)
            ->where(function (Builder $w) use ($ids) {
                $w->whereIn('regional_id', $ids)
                    ->orWhereHas('administration', fn (Builder $a) => $a->whereIn('regional_id', $ids))
                    ->orWhereHas('prayerHouse.administration', fn (Builder $a) => $a->whereIn('regional_id', $ids));
            });
    }
}
