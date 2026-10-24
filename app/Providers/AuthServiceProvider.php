<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Super-admin passa em can:/middleware de permissão (ex.: visualizar_relatorios).
        // impersonate fica de fora: a policy recusa a própria conta, super-admin e personificação encadeada.
        Gate::before(function (?User $user, string $ability) {
            if ($ability === 'impersonate') {
                return null;
            }

            if ($user?->isSuperAdmin()) {
                return true;
            }

            return null;
        });

        Gate::define('viewUserRoleDirectory', fn (User $user) => $user->isSuperAdmin() || $user->can('gerenciar_usuarios'));
    }
}
