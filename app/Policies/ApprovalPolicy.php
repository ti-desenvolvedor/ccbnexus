<?php

namespace App\Policies;

use App\Models\Approval;
use App\Models\Event;
use App\Models\User;

class ApprovalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->can('aprovar_evento');
    }

    public function view(User $user, Approval $approval): bool
    {
        return $this->viewAny($user) && $this->canAccessApprovableScope($user, $approval);
    }

    public function update(User $user, Approval $approval): bool
    {
        return $this->view($user, $approval);
    }

    public function delete(User $user, Approval $approval): bool
    {
        return $this->update($user, $approval);
    }

    protected function canAccessApprovableScope(User $user, Approval $approval): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $approval->loadMissing('approvable');
        $model = $approval->approvable;

        if ($model instanceof Event) {
            if ($model->regional_id === null) {
                return true;
            }

            $model->loadMissing('regional');

            return $model->regional !== null && $user->canAccessRegional($model->regional);
        }

        return false;
    }
}
