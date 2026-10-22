<?php

namespace App\Policies;

use App\Models\BaptismListReport;
use App\Models\User;

class BaptismListReportPolicy
{
    public function view(User $user, BaptismListReport $report): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->can('visualizar_relatorios')) {
            return false;
        }

        $report->loadMissing('regional');

        return $report->regional !== null && $user->canAccessRegional($report->regional);
    }

    public function confirm(User $user, BaptismListReport $report): bool
    {
        if (! $user->canMaintainSecretaria()) {
            return false;
        }

        if ($report->status !== BaptismListReport::STATUS_DRAFT) {
            return false;
        }

        return $this->view($user, $report);
    }
}
