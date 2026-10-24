<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->can('gerenciar_usuarios');
    }

    public function view(User $user, User $model): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($model->is_super_admin) {
            return false;
        }

        return $user->canManageUser($model);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, User $model): bool
    {
        return $this->view($user, $model);
    }

    public function sendPasswordReset(User $user, User $model): bool
    {
        if (! filled($model->email)) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $this->view($user, $model);
    }

    public function impersonate(User $user, User $model): bool
    {
        if (session()->has('nexus.impersonator_id')) {
            return false;
        }

        if ($user->is($model) || $model->is_super_admin) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->can('personificar_usuario') && $user->canManageUser($model);
    }
}
