<?php

namespace App\Support;

/**
 * Mapeia caminhos do projeto para laravel/ ou public_html/ no pacote FTP.
 */
final class FtpDeployLayout
{
    /**
     * @var list<string>
     */
    public const APP_DIRECTORIES = [
        'app',
        'bootstrap',
        'config',
        'database',
        'lang',
        'resources',
        'routes',
    ];

    /**
     * @var list<string>
     */
    public const APP_FILES = [
        'artisan',
        'composer.json',
        'composer.lock',
        '.env.example',
    ];

    /**
     * @var list<string>
     */
    public const PUBLIC_SKIP = [
        'index.php',
        'hot',
        'storage',
    ];

    /**
     * @var list<string>
     */
    public const FRONTEND_MARKERS = [
        'resources/js',
        'resources/css',
        'resources/views',
        'vite.config.js',
        'package.json',
        'package-lock.json',
    ];

    public static function markerPath(): string
    {
        return base_path('.ftp-last-deploy');
    }

    public static function manifestPath(): string
    {
        return base_path('.ftp-deploy-manifest.json');
    }

    /**
     * Destino relativo dentro do pacote FTP, ou null se o arquivo nao entra no deploy.
     */
    public static function packageRelativePath(string $projectRelative): ?string
    {
        $relative = str_replace('\\', '/', ltrim($projectRelative, '/'));

        if ($relative === '' || str_contains($relative, '..')) {
            return null;
        }

        if (str_ends_with($relative, '.sqlite') || str_ends_with($relative, '.sqlite-journal')) {
            return null;
        }

        if (str_starts_with($relative, 'public/')) {
            $underPublic = substr($relative, strlen('public/'));
            $top = explode('/', $underPublic)[0];

            if (in_array($top, self::PUBLIC_SKIP, true)) {
                return null;
            }

            return 'public_html/'.$underPublic;
        }

        if (str_starts_with($relative, 'storage/app/public/')) {
            $under = substr($relative, strlen('storage/app/public/'));

            return 'public_html/storage/'.$under;
        }

        foreach (self::APP_DIRECTORIES as $directory) {
            if ($relative === $directory || str_starts_with($relative, $directory.'/')) {
                return 'laravel/'.$relative;
            }
        }

        if (in_array($relative, self::APP_FILES, true)) {
            return 'laravel/'.$relative;
        }

        return null;
    }

    public static function isFrontendPath(string $projectRelative): bool
    {
        $relative = str_replace('\\', '/', $projectRelative);

        if (str_starts_with($relative, 'public/build/')) {
            return true;
        }

        foreach (self::FRONTEND_MARKERS as $marker) {
            if ($relative === $marker || str_starts_with($relative, rtrim($marker, '/').'/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public static function deployableRoots(): array
    {
        return [
            ...self::APP_DIRECTORIES,
            'public',
            'storage/app/public',
            ...self::APP_FILES,
        ];
    }
}
