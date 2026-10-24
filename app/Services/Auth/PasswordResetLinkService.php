<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class PasswordResetLinkService
{
    /**
     * Envia o link de redefinição para o e-mail do usuário.
     * A senha e o token não entram na resposta nem na auditoria.
     */
    public function send(User $actor, User $target): void
    {
        if (! $actor->can('sendPasswordReset', $target)) {
            throw new AuthorizationException('Sem permissão para enviar o link a este usuário.');
        }

        if (! filled($target->email)) {
            throw ValidationException::withMessages([
                'reset' => ['Este usuário não tem e-mail para receber o link.'],
            ]);
        }

        $status = Password::sendResetLink(['email' => $target->email]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'reset' => [__($status)],
            ]);
        }

        activity()
            ->causedBy($actor)
            ->performedOn($target)
            ->event('password_reset_link_sent')
            ->withProperties([
                'actor_id' => $actor->id,
                'target_id' => $target->id,
            ])
            ->log('Link de redefinição de senha enviado');
    }
}
