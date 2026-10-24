<?php

namespace App\Providers;

use App\Services\Auth\PwnedPasswordService;
use App\Mail\PasswordResetLinkMail;
use App\Services\Auth\ImpersonationService;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\Activitylog\Models\Activity;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Hostinger: app em laravel/, webroot em public_html/ (sem laravel/public).
        // DomPDF e CLI precisam resolver public_path(); o index.php já faz isto no HTTP.
        if (! is_dir(base_path('public'))) {
            $sharedPublic = base_path('../public_html');
            if (is_dir($sharedPublic)) {
                $this->app->usePublicPath($sharedPublic);
            }
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configurePublicStorageForSharedHosting();
        $this->ensureUploadDirectoriesExist();
        $this->configurePasswordDefaults();
        $this->app->singleton(UncompromisedVerifier::class, PwnedPasswordService::class);
        $this->usePlainPasswordResetMail();
        $this->tagActivityWhileImpersonating();
    }

    private function tagActivityWhileImpersonating(): void
    {
        Activity::creating(function (Activity $activity): void {
            $impersonatorId = session(ImpersonationService::SESSION_KEY);
            if (! $impersonatorId) {
                return;
            }

            $properties = collect($activity->properties);
            $properties->put('impersonator_id', (int) $impersonatorId);
            $activity->properties = $properties;
        });
    }

    private function usePlainPasswordResetMail(): void
    {
        ResetPasswordNotification::toMailUsing(function ($notifiable, string $token) {
            $email = $notifiable->getEmailForPasswordReset();
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $email,
            ], false));
            $minutes = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

            return new PasswordResetLinkMail($email, $url, $minutes);
        });
    }

    private function configurePasswordDefaults(): void
    {
        Password::defaults(function () {
            $min = (int) config('nexus.auth.password_min', 8);

            return Password::min(max(8, $min))
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised();
        });
    }

    /**
     * No pacote FTP (laravel/ + public_html/), o storage publico costuma ser
     * uma pasta real em public_html/storage (symlink nao funciona). Nesse caso,
     * gravamos uploads direto nela para a URL /storage/... funcionar.
     */
    private function configurePublicStorageForSharedHosting(): void
    {
        $defaultRoot = storage_path('app/public');
        if (config('filesystems.disks.public.root') !== $defaultRoot) {
            return;
        }

        $publicStorage = public_path('storage');

        if (! is_dir($publicStorage) || is_link($publicStorage)) {
            return;
        }

        Config::set('filesystems.disks.public.root', $publicStorage);
    }

    private function ensureUploadDirectoriesExist(): void
    {
        $paths = [
            storage_path('app/livewire-tmp'),
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs'),
            (string) config('filesystems.disks.public.root'),
        ];

        foreach ($paths as $path) {
            if ($path === '' || is_dir($path)) {
                continue;
            }

            @mkdir($path, 0775, true);
        }
    }
}
