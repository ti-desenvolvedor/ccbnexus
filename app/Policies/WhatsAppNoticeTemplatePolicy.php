<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WhatsAppNoticeTemplate;

class WhatsAppNoticeTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->can('gerenciar_avisos');
    }

    public function view(User $user, WhatsAppNoticeTemplate $template): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, WhatsAppNoticeTemplate $template): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        // Template padrão (oficial) exige moderação explícita.
        if ($template->is_default) {
            return $user->isSuperAdmin() || $user->can('moderar_conteudo');
        }

        return true;
    }

    public function setDefault(User $user): bool
    {
        return $user->isSuperAdmin() || $user->can('moderar_conteudo');
    }
}

