<?php

namespace App\Services;

use App\Models\AccessRequest;
use App\Models\User;
use App\Support\PasswordStrength;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class AccessRequestService
{
    /**
     * Aprova pedido, cria utilizador (se ainda não existir) e atribui role Visualizador por defeito.
     */
    public function approve(AccessRequest $request, User $reviewer, ?string $note = null, ?string $roleName = 'Visualizador'): User
    {
        if ($request->status !== 'pending') {
            throw new InvalidArgumentException(__('Este pedido já foi decidido.'));
        }

        return DB::transaction(function () use ($request, $reviewer, $note, $roleName) {
            $existing = User::query()->where('email', $request->email)->first();

            if ($existing) {
                $user = $existing;
            } else {
                $password = Str::password(16);
                $user = User::query()->create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    ...PasswordStrength::stored($password),
                    'email_verified_at' => now(),
                    'regional_id' => $request->regional_id,
                    'administration_id' => $request->administration_id,
                ]);
                $user->forceFill(['is_super_admin' => false])->save();
            }

            $safeRole = $this->safeRoleName($reviewer, $roleName);
            if ($safeRole && ! $user->hasRole($safeRole)) {
                $user->assignRole($safeRole);
            }

            $request->update([
                'status' => 'approved',
                'reviewed_by_user_id' => $reviewer->id,
                'reviewed_at' => now(),
                'review_note' => $note,
                'provisioned_user_id' => $user->id,
            ]);

            return $user;
        });
    }

    public function reject(AccessRequest $request, User $reviewer, ?string $note = null): void
    {
        if ($request->status !== 'pending') {
            throw new InvalidArgumentException(__('Este pedido já foi decidido.'));
        }

        if ($note === null || trim($note) === '') {
            throw new InvalidArgumentException(__('Justificativa obrigatória para rejeitar.'));
        }

        $request->update([
            'status' => 'rejected',
            'reviewed_by_user_id' => $reviewer->id,
            'reviewed_at' => now(),
            'review_note' => $note,
        ]);
    }

    protected function safeRoleName(User $reviewer, ?string $roleName): ?string
    {
        $roleName = $roleName ?: 'Visualizador';
        $blocked = ['Administrador'];

        if (in_array($roleName, $blocked, true) && ! $reviewer->isSuperAdmin()) {
            return 'Visualizador';
        }

        return $roleName;
    }
}
