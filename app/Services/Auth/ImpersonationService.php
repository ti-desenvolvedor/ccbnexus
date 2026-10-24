<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class ImpersonationService
{
    public const SESSION_KEY = 'nexus.impersonator_id';

    public const SESSION_STARTED_KEY = 'nexus.impersonation_started_at';

    public function start(User $actor, User $target, string $justification): void
    {
        $justification = trim($justification);

        $this->assertCanStart($actor, $target);

        if (mb_strlen($justification) < 10) {
            throw ValidationException::withMessages([
                'justification' => ['Descreva o motivo do suporte com pelo menos 10 caracteres.'],
            ]);
        }

        activity()
            ->causedBy($actor)
            ->performedOn($target)
            ->event('impersonation_started')
            ->withProperties([
                'impersonator_id' => $actor->id,
                'target_id' => $target->id,
                'justification' => $justification,
            ])
            ->log('Personificação iniciada');

        $this->remember($actor);
        Auth::login($target);
        $this->remember($actor);
    }

    /**
     * Dados da faixa: quem iniciou, quem está autenticado, início e âmbito.
     *
     * @return array{actor: User, as: User, startedAt: ?int, since: ?string, elapsed: string, scope: string}|null
     */
    public function banner(): ?array
    {
        $actorId = session(self::SESSION_KEY);
        $as = Auth::user();

        if (! $actorId || ! $as instanceof User) {
            return null;
        }

        $actor = User::query()->find($actorId);
        if (! $actor) {
            return null;
        }

        $as->loadMissing(['regional', 'administration', 'prayerHouse']);

        $startedAt = session(self::SESSION_STARTED_KEY);
        $startedAt = is_numeric($startedAt) ? (int) $startedAt : null;
        if (! $startedAt) {
            $startedAt = now()->getTimestamp();
            session()->put(self::SESSION_STARTED_KEY, $startedAt);
        }
        $started = Carbon::createFromTimestamp($startedAt);
        $seconds = $started ? max(0, now()->getTimestamp() - $startedAt) : 0;

        return [
            'actor' => $actor,
            'as' => $as,
            'startedAt' => $startedAt,
            'since' => $started->isToday() ? $started->format('H:i') : $started->format('d/m H:i'),
            'elapsed' => sprintf('%02d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60),
            'scope' => $this->scopeLabel($as),
        ];
    }

    public function stop(): void
    {
        $impersonatorId = session(self::SESSION_KEY);
        $target = Auth::user();

        if (! $impersonatorId || ! $target instanceof User) {
            throw new AuthorizationException('Não há personificação ativa.');
        }

        $impersonator = User::query()->find($impersonatorId);
        if (! $impersonator) {
            $this->forget();
            throw new AuthorizationException('A conta de origem não está mais disponível.');
        }

        $startedAt = session(self::SESSION_STARTED_KEY);
        $duration = is_numeric($startedAt) ? max(0, now()->getTimestamp() - (int) $startedAt) : null;

        activity()
            ->causedBy($impersonator)
            ->performedOn($target)
            ->event('impersonation_stopped')
            ->withProperties([
                'impersonator_id' => $impersonator->id,
                'target_id' => $target->id,
                'duration_seconds' => $duration,
            ])
            ->log('Personificação encerrada');

        $this->forget();
        Auth::login($impersonator);
    }

    private function remember(User $actor): void
    {
        session()->put(self::SESSION_KEY, $actor->id);

        if (! session()->has(self::SESSION_STARTED_KEY)) {
            session()->put(self::SESSION_STARTED_KEY, now()->getTimestamp());
        }
    }

    private function forget(): void
    {
        session()->forget([self::SESSION_KEY, self::SESSION_STARTED_KEY]);
    }

    private function scopeLabel(User $user): string
    {
        if ($user->prayerHouse) {
            return 'Casa de oração: '.$user->prayerHouse->name;
        }

        if ($user->administration) {
            return 'Administração: '.$user->administration->name;
        }

        if ($user->regional) {
            return 'Regional: '.$user->regional->name;
        }

        return 'Sem âmbito';
    }

    public function assertCanStart(User $actor, User $target): void
    {
        if (session()->has(self::SESSION_KEY)) {
            throw new AuthorizationException('Já existe uma personificação ativa.');
        }

        if ($actor->is($target) || $target->is_super_admin) {
            throw new AuthorizationException('Esta conta não pode ser personificada.');
        }

        if ($actor->isSuperAdmin()) {
            return;
        }

        if (! $this->hasPermission($actor) || ! $actor->canManageUser($target)) {
            throw new AuthorizationException('Sem permissão para personificar este usuário.');
        }
    }

    private function hasPermission(User $actor): bool
    {
        try {
            return $actor->hasPermissionTo('personificar_usuario');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }
}
