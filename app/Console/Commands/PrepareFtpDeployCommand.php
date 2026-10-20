<?php

namespace App\Console\Commands;

use App\Support\FtpDeployLayout;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Process;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class PrepareFtpDeployCommand extends Command
{
    protected $signature = 'ftp:prepare {--path=ftp-deploy : Pasta de destino com laravel/ e public_html/} {--skip-composer : Nao executar composer install no pacote} {--force : Recriar a pasta de destino se ja existir}';

    protected $description = 'Monta as pastas laravel/ e public_html/ para enviar por FTP na Hostinger compartilhada.';

    public function handle(Filesystem $files): int
    {
        $destination = $this->resolveDestination();
        $laravel = $destination.DIRECTORY_SEPARATOR.'laravel';
        $publicHtml = $destination.DIRECTORY_SEPARATOR.'public_html';

        if ($files->exists($destination) && ! $this->option('force')) {
            $this->error("A pasta {$destination} ja existe. Use --force para recriar.");

            return self::FAILURE;
        }

        if (! is_file(public_path('build/manifest.json'))) {
            $this->error('Falta public/build/manifest.json. Rode npm run build antes de gerar o pacote FTP.');

            return self::FAILURE;
        }

        if ($files->exists($destination)) {
            $files->deleteDirectory($destination);
        }

        $files->ensureDirectoryExists($laravel);
        $files->ensureDirectoryExists($publicHtml);

        foreach (FtpDeployLayout::APP_DIRECTORIES as $directory) {
            $source = base_path($directory);
            if ($files->isDirectory($source)) {
                $files->copyDirectory($source, $laravel.DIRECTORY_SEPARATOR.$directory);
            }
        }

        foreach (FtpDeployLayout::APP_FILES as $file) {
            $source = base_path($file);
            if ($files->exists($source)) {
                $files->copy($source, $laravel.DIRECTORY_SEPARATOR.$file);
            }
        }

        // Nunca empacotar o .env local (secrets). Usar apenas o template de exemplo.
        $envExample = base_path('.env.example');
        if ($files->exists($envExample)) {
            $files->copy($envExample, $laravel.DIRECTORY_SEPARATOR.'.env');
            $this->hardenProductionEnv($files, $laravel.DIRECTORY_SEPARATOR.'.env');
        }

        $this->copyStorageSkeleton($files, $laravel);
        $this->sanitizeLaravelPackage($files, $laravel);
        $this->ensureSqliteDatabase($files, $laravel);
        $this->copyPublicHtml($files, $publicHtml);
        $files->put($publicHtml.DIRECTORY_SEPARATOR.'index.php', $this->frontController());
        $files->put($laravel.DIRECTORY_SEPARATOR.'.htaccess', $this->denyWebAccess());
        $files->put($destination.DIRECTORY_SEPARATOR.'LEIA-ME.txt', $this->readme());

        if (! $this->option('skip-composer')) {
            $this->info('Instalando dependencias de producao em laravel/vendor...');

            $result = Process::path($laravel)
                ->timeout(600)
                ->run('composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist');

            $this->output->write($result->output());

            if ($result->failed()) {
                $this->error('composer install falhou. Verifique o Composer e tente de novo.');

                return self::FAILURE;
            }
        }

        $this->writeDeployMarkers($files);

        $this->newLine();
        $this->info('Pacote FTP pronto em: '.$destination);
        $this->line('  1. Envie a pasta laravel para a raiz da conta (ao lado de public_html).');
        $this->line('  2. Envie o CONTEUDO de public_html para o public_html do servidor.');
        $this->line('  3. Edite laravel/.env (APP_URL, MySQL, SMTP) e leia LEIA-ME.txt.');
        $this->line('  4. No SSH use /opt/alt/php85/usr/bin/php (nao o php padrao).');
        $this->line('  5. Para proximos envios: php artisan ftp:push --delta');
        $this->line('     (ou ftp:delta --force e depois ftp:push --path=ftp-deploy-delta)');

        return self::SUCCESS;
    }

    private function writeDeployMarkers(Filesystem $files): void
    {
        $payload = [
            'prepared_at' => now()->toIso8601String(),
            'commit' => $this->currentGitCommit(),
        ];

        $files->put(FtpDeployLayout::markerPath(), json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
        $files->put(FtpDeployLayout::manifestPath(), json_encode([
            ...$payload,
            'files' => $this->buildSourceManifest($files),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    }

    private function currentGitCommit(): ?string
    {
        $result = Process::path(base_path())
            ->run('git rev-parse HEAD');

        if ($result->failed()) {
            return null;
        }

        $hash = trim($result->output());

        return $hash !== '' ? $hash : null;
    }

    /**
     * @return array<string, string>
     */
    private function buildSourceManifest(Filesystem $files): array
    {
        $manifest = [];

        foreach (FtpDeployLayout::APP_DIRECTORIES as $directory) {
            $root = base_path($directory);
            if (! $files->isDirectory($root)) {
                continue;
            }

            foreach ($files->allFiles($root) as $file) {
                $relative = str_replace('\\', '/', substr($file->getPathname(), strlen(base_path()) + 1));
                if (FtpDeployLayout::packageRelativePath($relative) === null) {
                    continue;
                }
                $manifest[$relative] = hash_file('sha256', $file->getPathname()) ?: '';
            }
        }

        foreach (FtpDeployLayout::APP_FILES as $file) {
            $absolute = base_path($file);
            if ($files->isFile($absolute)) {
                $manifest[$file] = hash_file('sha256', $absolute) ?: '';
            }
        }

        $public = public_path();
        if ($files->isDirectory($public)) {
            foreach ($files->allFiles($public) as $file) {
                $relative = str_replace('\\', '/', substr($file->getPathname(), strlen(base_path()) + 1));
                if (FtpDeployLayout::packageRelativePath($relative) === null) {
                    continue;
                }
                $manifest[$relative] = hash_file('sha256', $file->getPathname()) ?: '';
            }
        }

        ksort($manifest);

        return $manifest;
    }

    private function resolveDestination(): string
    {
        $path = (string) $this->option('path');

        if ($this->isAbsolutePath($path)) {
            return rtrim($path, '\\/');
        }

        return base_path($path);
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/')
            || str_starts_with($path, '\\')
            || (bool) preg_match('/^[A-Za-z]:[\\\\\\/]/', $path);
    }

    private function sanitizeLaravelPackage(Filesystem $files, string $laravel): void
    {
        foreach (['config.php', 'packages.php', 'services.php', 'routes-v7.php', 'events.php'] as $cached) {
            $files->delete($laravel.DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.$cached);
        }

        $this->deleteGlob($files, $laravel.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'*.sqlite');
        $this->deleteGlob($files, $laravel.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'*.sqlite-journal');
        $this->deleteGlob($files, $laravel.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'logs'.DIRECTORY_SEPARATOR.'*.log');
        $this->deleteGlob($files, $laravel.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'framework'.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'*.php');
        $this->deleteGlob($files, $laravel.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'framework'.DIRECTORY_SEPARATOR.'sessions'.DIRECTORY_SEPARATOR.'*');
        $this->deleteGlob($files, $laravel.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'framework'.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'*');
    }

    private function copyStorageSkeleton(Filesystem $files, string $laravel): void
    {
        foreach ([
            'bootstrap/cache',
            'storage/app',
            'storage/app/public',
            'storage/app/private',
            'storage/framework',
            'storage/framework/cache',
            'storage/framework/cache/data',
            'storage/framework/sessions',
            'storage/framework/views',
            'storage/logs',
        ] as $directory) {
            $relative = str_replace('/', DIRECTORY_SEPARATOR, $directory);
            $files->ensureDirectoryExists($laravel.DIRECTORY_SEPARATOR.$relative);

            $gitignore = base_path($relative.DIRECTORY_SEPARATOR.'.gitignore');
            if ($files->exists($gitignore)) {
                $files->copy($gitignore, $laravel.DIRECTORY_SEPARATOR.$relative.DIRECTORY_SEPARATOR.'.gitignore');
            }
        }
    }

    private function ensureSqliteDatabase(Filesystem $files, string $laravel): void
    {
        $databaseDir = $laravel.DIRECTORY_SEPARATOR.'database';
        $files->ensureDirectoryExists($databaseDir);

        $sqlite = $databaseDir.DIRECTORY_SEPARATOR.'database.sqlite';
        if (! $files->exists($sqlite)) {
            $files->put($sqlite, '');
        }
    }

    private function copyPublicHtml(Filesystem $files, string $publicHtml): void
    {
        $public = public_path();
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($public, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        /** @var SplFileInfo $item */
        foreach ($iterator as $item) {
            if ($item->isLink()) {
                continue;
            }

            $relative = str_replace('\\', '/', substr($item->getPathname(), strlen($public) + 1));
            $top = explode('/', $relative)[0];

            if (in_array($top, FtpDeployLayout::PUBLIC_SKIP, true)) {
                continue;
            }

            $target = $publicHtml.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);

            if ($item->isDir()) {
                $files->ensureDirectoryExists($target);

                continue;
            }

            if (! $item->isFile()) {
                continue;
            }

            $files->ensureDirectoryExists(dirname($target));
            $files->copy($item->getPathname(), $target);
        }
    }

    private function hardenProductionEnv(Filesystem $files, string $envPath): void
    {
        $env = $files->get($envPath);
        $env = preg_replace('/^APP_ENV=.*/m', 'APP_ENV=production', $env) ?? $env;
        $env = preg_replace('/^APP_DEBUG=.*/m', 'APP_DEBUG=false', $env) ?? $env;
        $env = preg_replace('/^APP_URL=.*/m', 'APP_URL=https://ccb.tinexus.com.br', $env) ?? $env;
        $env = preg_replace('/^LOG_LEVEL=.*/m', 'LOG_LEVEL=error', $env) ?? $env;
        // Em shared hosting sem MySQL ainda configurado, evita 500 por sessao/cache em DB.
        $env = preg_replace('/^SESSION_DRIVER=.*/m', 'SESSION_DRIVER=file', $env) ?? $env;
        $env = preg_replace('/^CACHE_STORE=.*/m', 'CACHE_STORE=file', $env) ?? $env;
        $env = preg_replace('/^QUEUE_CONNECTION=.*/m', 'QUEUE_CONNECTION=sync', $env) ?? $env;
        $files->put($envPath, $env);
    }

    private function deleteGlob(Filesystem $files, string $pattern): void
    {
        foreach (glob($pattern) ?: [] as $path) {
            if (is_file($path) && basename($path) !== '.gitignore') {
                $files->delete($path);
            }
        }
    }

    private function frontController(): string
    {
        return <<<'PHP'
<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$laravelRoot = __DIR__.'/../laravel';

if (file_exists($maintenance = $laravelRoot.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $laravelRoot.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $laravelRoot.'/bootstrap/app.php';

$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
PHP;
    }

    private function denyWebAccess(): string
    {
        return <<<'HTACCESS'
<IfModule mod_authz_core.c>
    Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
    Deny from all
</IfModule>
HTACCESS;
    }

    private function readme(): string
    {
        return <<<'TXT'
CCB Nexus — pacote para FTP (Hostinger compartilhada)
Dominio: https://ccb.tinexus.com.br

Estrutura na raiz da conta (mesmo nivel):

  ~/domains/ccb.tinexus.com.br/laravel/        <- codigo da aplicacao
  ~/domains/ccb.tinexus.com.br/public_html/    <- document root do site

Como enviar
-----------
1. No FileZilla, entre na RAIZ do dominio ccb.tinexus.com.br (onde ja existe public_html).
2. Envie a pasta "laravel" inteira para essa raiz.
3. Abra a pasta "public_html" DESTE pacote e envie o CONTEUDO dela
   para dentro do public_html do servidor (nao coloque uma pasta
   public_html dentro da outra).
4. No hPanel, selecione PHP 8.3 ou superior (8.5 se estiver disponivel).
   No SSH use SEMPRE /opt/alt/php85/usr/bin/php — o comando "php" padrao
   pode ser uma versao antiga. Se php85 nao existir, use
   /opt/alt/php84/usr/bin/php.

Arquivo .env (obrigatorio, no servidor)
---------------------------------------
Edite laravel/.env. O pacote ja deixa APP_ENV=production e APP_DEBUG=false.
Troque o resto antes de abrir o site:

  APP_URL=https://ccb.tinexus.com.br
  LOG_LEVEL=error

MySQL (cole do hPanel → MySQL Databases — NAO invente senha):
  DB_CONNECTION=mysql
  DB_HOST=localhost
  DB_PORT=3306
  DB_DATABASE=...
  DB_USERNAME=...
  DB_PASSWORD="..."

Em hospedagem compartilhada o host quase sempre e localhost
(nao use o dominio do site como DB_HOST).

E-mail / avisos
---------------
No hPanel crie uma caixa contato@ccb.tinexus.com.br e preencha:

  MAIL_MAILER=smtp
  MAIL_HOST=smtp.hostinger.com
  MAIL_PORT=465
  MAIL_USERNAME=contato@ccb.tinexus.com.br
  MAIL_PASSWORD=
  MAIL_FROM_ADDRESS=contato@ccb.tinexus.com.br
  MAIL_FROM_NAME="${APP_NAME}"

Permissoes
----------
laravel/storage              gravavel (775)
laravel/bootstrap/cache      gravavel (775)
laravel/database             gravavel so se ficar em sqlite (prefira MySQL)

Apos o upload (SSH)
--------------------------------------------
  cd ~/domains/ccb.tinexus.com.br/laravel

  # Se o site pedir database.sqlite e o ficheiro nao existir:
  touch database/database.sqlite
  chmod 664 database/database.sqlite
  chmod 775 database storage bootstrap/cache

  # Preferir enviar vendor/ do PC (composer no servidor pode falhar por proc_open / php errado).
  # Se precisar:
  # /opt/alt/php85/usr/bin/php /usr/local/bin/composer install --no-dev --no-scripts --optimize-autoloader --no-interaction

  /opt/alt/php85/usr/bin/php artisan key:generate --force
  /opt/alt/php85/usr/bin/php artisan migrate --force
  /opt/alt/php85/usr/bin/php artisan nexus:seed-catalog
  /opt/alt/php85/usr/bin/php artisan storage:link
  /opt/alt/php85/usr/bin/php artisan optimize:clear
  /opt/alt/php85/usr/bin/php artisan optimize

Se php85 nao existir neste host, troque por /opt/alt/php84/usr/bin/php.

Conferir
--------
  https://ccb.tinexus.com.br/status   deve responder JSON ok
  home / dashboard com CSS/JS (pasta public_html/build)

Se o FTP so abrir dentro de public_html
---------------------------------------
A pasta laravel precisa ficar AO LADO de public_html, nao dentro.

Proximos envios parciais (so o que mudou)
-----------------------------------------
  php artisan ftp:push --delta
  php artisan ftp:delta --force
  php artisan ftp:delta --force --from-manifest
  php artisan ftp:delta --force --since-mtime="2026-10-05 10:00"

TXT;
    }
}
