<?php

namespace App\Http\Controllers\Auth;

use App\Services\Auth\ImpersonationService;
use Illuminate\Http\RedirectResponse;

class StopImpersonationController
{
    public function __invoke(ImpersonationService $impersonation): RedirectResponse
    {
        $impersonation->stop();

        return new RedirectResponse(route('dashboard'));
    }
}
