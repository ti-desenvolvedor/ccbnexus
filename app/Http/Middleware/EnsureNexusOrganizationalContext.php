<?php

namespace App\Http\Middleware;

use App\Models\Regional;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNexusOrganizationalContext
{
    /**
     * Garante que o contexto ativo na sessão é utilizável pelo utilizador autenticado.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $rid = session('nexus.active_regional_id');
        if ($rid !== null && $rid !== '') {
            $regional = Regional::query()->find((int) $rid);
            if ($regional && ! $user->canAccessRegional($regional)) {
                abort(403, __('Contexto regional inválido.'));
            }
        }

        return $next($request);
    }
}
