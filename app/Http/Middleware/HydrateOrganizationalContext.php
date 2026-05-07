<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HydrateOrganizationalContext
{
    /**
     * Preenche chaves de contexto na sessão quando ainda não existem.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        if (! session()->exists('nexus.active_regional_id')) {
            session(['nexus.active_regional_id' => $user->regional_id]);
        }
        if (! session()->exists('nexus.active_administration_id')) {
            session(['nexus.active_administration_id' => $user->administration_id]);
        }
        if (! session()->exists('nexus.active_prayer_house_id')) {
            session(['nexus.active_prayer_house_id' => $user->prayer_house_id]);
        }

        return $next($request);
    }
}
