<?php

namespace App\Policies\Concerns;

use App\Models\Regional;
use App\Models\User;

trait ManagesPublicAudienceCatalog
{
    public const string PERMISSION_MANAGE = 'gerenciar_avisos';

    public const string PERMISSION_DELETE = 'excluir_catalogo_publico';

    protected function managesPublicAudienceCatalog(User $user): bool
    {
        return $user->isSuperAdmin() || $user->can(self::PERMISSION_MANAGE);
    }

    protected function canDeletePublicAudienceCatalog(User $user): bool
    {
        return $user->isSuperAdmin() || $user->can(self::PERMISSION_DELETE);
    }

    /**
     * Permissão de catálogo + acesso à regional do registo (super-admin passa sempre).
     */
    protected function canAccessCatalogRegional(User $user, ?Regional $regional): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($regional === null) {
            return false;
        }

        return $user->canAccessRegional($regional);
    }
}
