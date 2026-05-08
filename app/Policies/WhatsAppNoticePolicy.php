<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;
use App\Models\WhatsAppNotice;

class WhatsAppNoticePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->can('visualizar_evento');
    }

    public function view(User $user, WhatsAppNotice $notice): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($notice->regional_id === null) {
            return true;
        }

        return in_array((int) $notice->regional_id, $user->accessibleRegionalIds(), true);
    }

    public function createFromEvent(User $user, Event $event): bool
    {
        return ($user->isSuperAdmin() || $user->can('editar_evento')) && $user->can('view', $event);
    }

    public function markSent(User $user, WhatsAppNotice $notice): bool
    {
        // “Enviar manual” é uma ação operacional de avisos.
        return ($user->isSuperAdmin() || $user->can('gerenciar_avisos')) && $this->view($user, $notice);
    }
}

