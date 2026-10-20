<?php

namespace App\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
    }

    private function configurePasswordDefaults(): void
    {
        Password::defaults(function () {
            $min = (int) config('nexus.auth.password_min', 8);

            return Password::min(max(8, $min))
                ->letters()
                ->numbers()
                ->symbols();
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
