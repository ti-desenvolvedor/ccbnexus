<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->can('visualizar_evento');
    }

    public function view(User $user, Event $event): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($event->regional_id === null) {
            return true;
        }

        return $user->canAccessRegional($event->regional);
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->can('criar_evento');
    }

    public function update(User $user, Event $event): bool
    {
        return ($user->isSuperAdmin() || $user->can('editar_evento')) && $this->view($user, $event);
    }

    public function cancel(User $user, Event $event): bool
    {
        return ($user->isSuperAdmin() || $user->can('cancelar_evento')) && $this->view($user, $event);
    }

    public function approve(User $user, Event $event): bool
    {
        return ($user->isSuperAdmin() || $user->can('aprovar_evento')) && $this->view($user, $event);
    }

    /**
     * Utilizador autenticado pode registar a sua confirmação de participação / refeições.
     */
    public function respond(User $user, Event $event): bool
    {
        if ($event->status === 'cancelled') {
            return false;
        }

        return $this->view($user, $event);
    }
}
