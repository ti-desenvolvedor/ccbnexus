<?php

namespace App\Services;

use App\Models\BaptismListReport;
use App\Models\BaptismListReportCheck;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BaptismListReportConfirmation
{
    public function check(BaptismListReport $report, User $user, ?int $activeRegionalId): BaptismListReport
    {
        $this->assertActor($report, $user, $activeRegionalId);

        return DB::transaction(function () use ($report, $user) {
            $locked = BaptismListReport::query()->whereKey($report->id)->lockForUpdate()->firstOrFail();
            $this->assertOpen($locked);
            $this->assertNoVisiblePending($locked);

            BaptismListReportCheck::query()->updateOrCreate(
                [
                    'baptism_list_report_id' => $locked->id,
                    'user_id' => $user->id,
                ],
                ['checked_at' => now()],
            );

            $this->log($locked, $user, 'checked', 'Lista de batismos conferida');

            return $locked->refresh();
        });
    }

    public function uncheck(BaptismListReport $report, User $user, ?int $activeRegionalId): BaptismListReport
    {
        $this->assertActor($report, $user, $activeRegionalId);

        return DB::transaction(function () use ($report, $user) {
            $locked = BaptismListReport::query()->whereKey($report->id)->lockForUpdate()->firstOrFail();
            $this->assertOpen($locked);

            $own = $locked->checks()->where('user_id', $user->id);
            if ($locked->status === BaptismListReport::STATUS_READY && $own->exists() && $locked->checks()->count() <= 1) {
                throw ValidationException::withMessages([
                    'list_check' => [__('A lista aprovada precisa manter pelo menos uma verificação.')],
                ]);
            }

            $own->delete();
            $this->log($locked, $user, 'unchecked', 'Conferência da lista retirada');

            return $locked->refresh();
        });
    }

    public function approve(BaptismListReport $report, User $user, ?int $activeRegionalId): BaptismListReport
    {
        $this->assertActor($report, $user, $activeRegionalId);
        if (! $user->canMaintainSecretaria()) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($report, $user) {
            $locked = BaptismListReport::query()->whereKey($report->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== BaptismListReport::STATUS_DRAFT) {
                throw ValidationException::withMessages([
                    'publication' => [__('Só uma lista em rascunho pode ser aprovada para publicação.')],
                ]);
            }

            if (! $locked->checks()->exists()) {
                throw ValidationException::withMessages([
                    'publication' => [__('É preciso pelo menos uma verificação antes de aprovar a publicação.')],
                ]);
            }

            $this->assertNoVisiblePending($locked, 'publication');

            $meta = $locked->meta ?? [];
            unset($meta['rejection']);
            $meta['publication'] = [
                'approved_by' => $user->id,
                'approved_at' => now()->toIso8601String(),
            ];

            $locked->update([
                'status' => BaptismListReport::STATUS_READY,
                'meta' => $meta,
            ]);

            $this->log($locked, $user, 'approved', 'Lista de batismos aprovada para publicação');

            return $locked->refresh();
        });
    }

    public function reject(BaptismListReport $report, User $user, string $reason, ?int $activeRegionalId): BaptismListReport
    {
        $this->assertActor($report, $user, $activeRegionalId);
        if (! $user->canMaintainSecretaria()) {
            throw new AuthorizationException;
        }

        $reason = trim($reason);
        $length = mb_strlen($reason);
        if ($length < 8 || $length > 500) {
            throw ValidationException::withMessages([
                'rejection_reason' => [__('Escreva o que precisa ser ajustado.')],
            ]);
        }

        return DB::transaction(function () use ($report, $user, $reason) {
            $locked = BaptismListReport::query()->whereKey($report->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== BaptismListReport::STATUS_DRAFT) {
                throw ValidationException::withMessages([
                    'rejection_reason' => [__('Só uma lista em rascunho pode ser reprovada.')],
                ]);
            }

            $meta = $locked->meta ?? [];
            $meta['rejection'] = [
                'reason' => $reason,
                'rejected_by' => $user->id,
                'rejected_at' => now()->toIso8601String(),
            ];

            $locked->update(['meta' => $meta]);
            $this->log($locked, $user, 'rejected', 'Lista de batismos reprovada');

            return $locked->refresh();
        });
    }

    protected function assertActor(BaptismListReport $report, User $user, ?int $activeRegionalId): void
    {
        if ($activeRegionalId === null || (int) $activeRegionalId !== (int) $report->regional_id) {
            throw new AuthorizationException;
        }

        if ($user->isSuperAdmin()) {
            return;
        }

        if (! $user->can('visualizar_relatorios')) {
            throw new AuthorizationException;
        }

        $report->loadMissing('regional');
        if (! $report->regional || ! $user->canAccessRegional($report->regional)) {
            throw new AuthorizationException;
        }
    }

    protected function assertOpen(BaptismListReport $report): void
    {
        if (! in_array($report->status, [BaptismListReport::STATUS_DRAFT, BaptismListReport::STATUS_READY], true)) {
            throw ValidationException::withMessages([
                'list_check' => [__('Esta versão da lista não aceita conferência.')],
            ]);
        }
    }

    protected function assertNoVisiblePending(BaptismListReport $report, string $field = 'list_check'): void
    {
        $hasVisiblePending = $report->items()
            ->where('is_visible', true)
            ->where('payload->status', 'pending_approval')
            ->exists();

        if ($hasVisiblePending) {
            throw ValidationException::withMessages([
                $field => [__('Publique ou oculte os itens pendentes antes de seguir.')],
            ]);
        }
    }

    protected function log(BaptismListReport $report, User $user, string $event, string $description): void
    {
        activity()
            ->performedOn($report)
            ->causedBy($user)
            ->event($event)
            ->withProperties([
                'revision' => $report->revision,
                'regional_id' => $report->regional_id,
                'user_id' => $user->id,
            ])
            ->log($description);
    }
}
