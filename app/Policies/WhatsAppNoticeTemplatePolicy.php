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
        return $this->viewAny($user) && $this->canAccessTemplateRegional($user, $template);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, WhatsAppNoticeTemplate $template): bool
    {
        if (! $this->view($user, $template)) {
            return false;
        }

        // Template padrão (oficial) exige moderação explícita.
        if ($template->is_default) {
            return $user->isSuperAdmin() || $user->can('moderar_conteudo');
        }

        return true;
    }

    public function delete(User $user, WhatsAppNoticeTemplate $template): bool
    {
        return $this->update($user, $template);
    }

    public function setDefault(User $user): bool
    {
        return $user->isSuperAdmin() || $user->can('moderar_conteudo');
    }

    protected function canAccessTemplateRegional(User $user, WhatsAppNoticeTemplate $template): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Templates globais (sem regional) são visíveis a quem gere avisos.
        if ($template->regional_id === null) {
            return true;
        }

        $template->loadMissing('regional');

        return $template->regional !== null && $user->canAccessRegional($template->regional);
    }
}
