<?php

namespace App\Services;

use App\Models\BaptismListReport;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BaptismListReportConfirmation
{
    public function confirm(
        BaptismListReport $report,
        User $user,
        string $justification,
        ?int $activeRegionalId,
    ): BaptismListReport {
        if (! $user->canMaintainSecretaria()) {
            throw new AuthorizationException;
        }

        if ($activeRegionalId === null || (int) $activeRegionalId !== (int) $report->regional_id) {
            throw new AuthorizationException;
        }

        if (! $user->isSuperAdmin()) {
            $report->loadMissing('regional');
            if (! $report->regional || ! $user->canAccessRegional($report->regional)) {
                throw new AuthorizationException;
            }
        }

        $justification = trim($justification);
        $length = mb_strlen($justification);
        if ($length < 8 || $length > 500) {
            throw ValidationException::withMessages([
                'confirmation_justification' => [__('Escreva a justificativa para confirmar a lista.')],
            ]);
        }

        return DB::transaction(function () use ($report, $user, $justification) {
            $locked = BaptismListReport::query()->whereKey($report->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== BaptismListReport::STATUS_DRAFT) {
                throw ValidationException::withMessages([
                    'confirmation_justification' => [__('Só uma lista em rascunho pode ser confirmada.')],
                ]);
            }

            $hasVisiblePending = $locked->items()
                ->where('is_visible', true)
                ->where('payload->status', 'pending_approval')
                ->exists();

            if ($hasVisiblePending) {
                throw ValidationException::withMessages([
                    'confirmation_justification' => [__('Publique ou oculte os itens pendentes antes de confirmar a lista.')],
                ]);
            }

            $meta = $locked->meta ?? [];
            $meta['confirmation'] = [
                'justification' => $justification,
                'confirmed_by' => $user->id,
                'confirmed_at' => now()->toIso8601String(),
            ];

            $locked->update([
                'status' => BaptismListReport::STATUS_READY,
                'meta' => $meta,
            ]);

            activity()
                ->performedOn($locked)
                ->causedBy($user)
                ->event('confirmed')
                ->withProperties([
                    'justification' => $justification,
                    'revision' => $locked->revision,
                    'regional_id' => $locked->regional_id,
                ])
                ->log('Lista de batismos confirmada');

            return $locked->refresh();
        });
    }
}
