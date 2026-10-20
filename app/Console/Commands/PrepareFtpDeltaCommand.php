<?php

namespace App\Console\Commands;

use App\Support\FtpDeployLayout;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Process;
use Throwable;

class PrepareFtpDeltaCommand extends Command
{
    protected $signature = 'ftp:delta
    {--path=ftp-deploy-delta : Pasta de destino com laravel/ e public_html/ so com o que mudou}
    {--force : Recriar a pasta de destino se ja existir}
    {--since= : Ref git (commit, tag, HEAD~N). Padrao: commit em .ftp-last-deploy}
    {--from-manifest : Comparar hashes com .ftp-deploy-manifest.json (ignora git)}
    {--since-mtime= : Incluir arquivos com mtime >= data/hora local (ex: "2026-10-05 10:00")}
';

    protected $description = 'Monta um pacote FTP parcial so com arquivos alterados desde o ultimo prepare ou uma ref git.';

    public function handle(Filesystem $files): int
    {
        $destination = $this->resolveDestination();

        if ($files->exists($destination) && ! $this->option('force')) {
            $this->error("A pasta {$destination} ja existe. Use --force para recriar.");

            return self::FAILURE;
        }

        if (! is_file(public_path('build/manifest.json'))) {
            $this->error('Falta public/build/manifest.json. Rode npm run build antes de gerar o pacote delta.');

            return self::FAILURE;
        }

        $selection = $this->selectChangedPaths($files);

        if ($selection === null) {
            return self::FAILURE;
        }

        /** @var list<string> $changed */
        $changed = $selection['paths'];
        $basis = $selection['basis'];

        if ($changed === []) {
            $this->warn('Nenhum arquivo deployavel mudou desde a base escolhida.');
            $this->line('Base: '.$basis);

            return self::SUCCESS;
        }

        $needsFullBuild = $this->shouldIncludeBuiltAssets($changed);
        $packagePaths = $this->expandPackagePaths($files, $changed, $needsFullBuild);

        if ($files->exists($destination)) {
            $files->deleteDirectory($destination);
        }

        $copied = [];
        foreach ($packagePaths as $projectRelative) {
            $packageRelative = FtpDeployLayout::packageRelativePath($projectRelative);
            if ($packageRelative === null) {
                continue;
            }

            $source = base_path(str_replace('/', DIRECTORY_SEPARATOR, $projectRelative));
            if (! $files->isFile($source)) {
                continue;
            }

            $target = $destination.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $packageRelative);
            $files->ensureDirectoryExists(dirname($target));
            $files->copy($source, $target);
            $copied[] = $projectRelative;
        }

        sort($copied);

        $files->put(
            $destination.DIRECTORY_SEPARATOR.'LEIA-ME.txt',
            $this->readme($basis, $copied, $needsFullBuild),
        );

        $this->newLine();
        $this->info('Pacote FTP delta pronto em: '.$destination);
        $this->line('Base: '.$basis);
        $this->line('Arquivos: '.count($copied));
        if ($needsFullBuild) {
            $this->line('Incluiu public/build completo (assets frontend alterados).');
        }
        $this->line('Leia LEIA-ME.txt; envio automatico: php artisan ftp:push --path=ftp-deploy-delta');
        $this->line('Ou num so passo: php artisan ftp:push --delta');

        $this->writeDeployMarkers($files);

        return self::SUCCESS;
    }

    /**
     * @return array{paths: list<string>, basis: string}|null
     */
    private function selectChangedPaths(Filesystem $files): ?array
    {
        if ($this->option('since-mtime')) {
            try {
                $timezone = 'America/Sao_Paulo';
                $since = Carbon::parse((string) $this->option('since-mtime'), $timezone);
            } catch (Throwable) {
                $this->error('Data invalida em --since-mtime. Use algo como "2026-10-05 10:00" (horario de America/Sao_Paulo).');

                return null;
            }

            return [
                'paths' => $this->pathsChangedSinceMtime($files, $since),
                'basis' => 'mtime >= '.$since->timezone($timezone)->toDateTimeString().' ('.$timezone.')',
            ];
        }

        if ($this->option('from-manifest') || $this->shouldPreferManifest()) {
            $manifestResult = $this->pathsChangedFromManifest($files);
            if ($manifestResult === null) {
                return null;
            }

            return $manifestResult;
        }

        $since = $this->resolveGitSinceRef();
        if ($since === null) {
            if (is_file(FtpDeployLayout::manifestPath())) {
                $this->warn('Sem commit em .ftp-last-deploy; usando manifesto de hashes.');

                return $this->pathsChangedFromManifest($files);
            }

            $timezone = 'America/Sao_Paulo';
            $since = Carbon::today($timezone);
            $this->warn(
                'Sem git/manifesto. Incluindo arquivos alterados desde '.
                $since->toDateTimeString().' ('.$timezone.').'
            );
            $this->line('Primeira vez no servidor? Rode php artisan ftp:prepare --force. Depois disso, --delta usa o manifesto.');

            return [
                'paths' => $this->pathsChangedSinceMtime($files, $since),
                'basis' => 'auto mtime >= '.$since->toDateTimeString().' ('.$timezone.')',
            ];
        }

        $paths = $this->pathsChangedSinceGit($since);
        if ($paths === null) {
            return null;
        }

        return [
            'paths' => $paths,
            'basis' => 'git '.$since.'..HEAD (+ working tree)',
        ];
    }

    private function shouldPreferManifest(): bool
    {
        if ($this->option('since')) {
            return false;
        }

        $marker = $this->readLastDeployMarker();
        if (($marker['commit'] ?? null) !== null) {
            return false;
        }

        return is_file(FtpDeployLayout::manifestPath());
    }

    /**
     * @return array{paths: list<string>, basis: string}|null
     */
    private function pathsChangedFromManifest(Filesystem $files): ?array
    {
        $path = FtpDeployLayout::manifestPath();
        if (! $files->isFile($path)) {
            $this->error('Nao ha .ftp-deploy-manifest.json. Rode php artisan ftp:prepare --force antes, ou use --since / --since-mtime.');

            return null;
        }

        /** @var array{files?: array<string, string>}|null $manifest */
        $manifest = json_decode($files->get($path), true);
        if (! is_array($manifest) || ! isset($manifest['files']) || ! is_array($manifest['files'])) {
            $this->error('Manifesto .ftp-deploy-manifest.json invalido.');

            return null;
        }

        $previous = $manifest['files'];
        $current = $this->scanCurrentHashes($files);
        $changed = [];

        foreach ($current as $relative => $hash) {
            if (! array_key_exists($relative, $previous) || $previous[$relative] !== $hash) {
                $changed[] = $relative;
            }
        }

        sort($changed);

        $preparedAt = is_string($manifest['prepared_at'] ?? null) ? $manifest['prepared_at'] : 'manifesto';

        return [
            'paths' => $changed,
            'basis' => 'manifesto (.ftp-deploy-manifest.json de '.$preparedAt.')',
        ];
    }

    /**
     * @return list<string>
     */
    private function pathsChangedSinceMtime(Filesystem $files, Carbon $since): array
    {
        $threshold = $since->getTimestamp();
        $changed = [];

        foreach ($this->allDeployableFiles($files) as $relative => $absolute) {
            if (filemtime($absolute) >= $threshold) {
                $changed[] = $relative;
            }
        }

        sort($changed);

        return $changed;
    }

    /**
     * @return list<string>|null
     */
    private function pathsChangedSinceGit(string $since): ?array
    {
        $diff = Process::path(base_path())
            ->run(['git', 'diff', '--name-only', '--diff-filter=ACMR', $since]);

        if ($diff->failed()) {
            $this->error('Nao foi possivel listar mudancas git desde '.$since.'.');
            $this->line(trim($diff->errorOutput()) !== '' ? trim($diff->errorOutput()) : trim($diff->output()));

            return null;
        }

        $untracked = Process::path(base_path())
            ->run(['git', 'ls-files', '--others', '--exclude-standard']);

        $paths = array_merge(
            preg_split("/\r\n|\n|\r/", trim($diff->output())) ?: [],
            $untracked->successful() ? (preg_split("/\r\n|\n|\r/", trim($untracked->output())) ?: []) : [],
        );

        $changed = [];
        foreach ($paths as $path) {
            $path = str_replace('\\', '/', trim($path));
            if ($path === '' || FtpDeployLayout::packageRelativePath($path) === null) {
                continue;
            }
            if (! is_file(base_path(str_replace('/', DIRECTORY_SEPARATOR, $path)))) {
                continue;
            }
            $changed[] = $path;
        }

        $changed = array_values(array_unique($changed));
        sort($changed);

        return $changed;
    }

    private function resolveGitSinceRef(): ?string
    {
        $explicit = trim((string) $this->option('since'));
        if ($explicit !== '') {
            return $explicit;
        }

        $marker = $this->readLastDeployMarker();
        if (is_string($marker['commit'] ?? null) && $marker['commit'] !== '') {
            return $marker['commit'];
        }

        return null;
    }

    /**
     * @return array{prepared_at?: string, commit?: string|null}
     */
    private function readLastDeployMarker(): array
    {
        $path = FtpDeployLayout::markerPath();
        if (! is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  list<string>  $changed
     */
    private function shouldIncludeBuiltAssets(array $changed): bool
    {
        foreach ($changed as $path) {
            if (FtpDeployLayout::isFrontendPath($path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $changed
     * @return list<string>
     */
    private function expandPackagePaths(Filesystem $files, array $changed, bool $needsFullBuild): array
    {
        $paths = $changed;

        if ($needsFullBuild) {
            $build = public_path('build');
            if ($files->isDirectory($build)) {
                foreach ($files->allFiles($build) as $file) {
                    $relative = str_replace('\\', '/', substr($file->getPathname(), strlen(base_path()) + 1));
                    $paths[] = $relative;
                }
            }
        }

        $paths = array_values(array_unique($paths));
        sort($paths);

        return $paths;
    }

    /**
     * @return array<string, string>
     */
    private function scanCurrentHashes(Filesystem $files): array
    {
        $manifest = [];

        foreach ($this->allDeployableFiles($files) as $relative => $absolute) {
            $manifest[$relative] = hash_file('sha256', $absolute) ?: '';
        }

        ksort($manifest);

        return $manifest;
    }

    /**
     * @return array<string, string> relative => absolute
     */
    private function allDeployableFiles(Filesystem $files): array
    {
        $map = [];

        foreach (FtpDeployLayout::APP_DIRECTORIES as $directory) {
            $root = base_path($directory);
            if (! $files->isDirectory($root)) {
                continue;
            }

            foreach ($files->allFiles($root) as $file) {
                $relative = str_replace('\\', '/', substr($file->getPathname(), strlen(base_path()) + 1));
                if (FtpDeployLayout::packageRelativePath($relative) !== null) {
                    $map[$relative] = $file->getPathname();
                }
            }
        }

        foreach (FtpDeployLayout::APP_FILES as $file) {
            $absolute = base_path($file);
            if ($files->isFile($absolute)) {
                $map[$file] = $absolute;
            }
        }

        $public = public_path();
        if ($files->isDirectory($public)) {
            foreach ($files->allFiles($public) as $file) {
                $relative = str_replace('\\', '/', substr($file->getPathname(), strlen(base_path()) + 1));
                if (FtpDeployLayout::packageRelativePath($relative) !== null) {
                    $map[$relative] = $file->getPathname();
                }
            }
        }

        $uploads = storage_path('app/public');
        if ($files->isDirectory($uploads)) {
            foreach ($files->allFiles($uploads) as $file) {
                if ($file->getFilename() === '.gitignore') {
                    continue;
                }
                $relative = str_replace('\\', '/', substr($file->getPathname(), strlen(base_path()) + 1));
                $map[$relative] = $file->getPathname();
            }
        }

        return $map;
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

    /**
     * @param  list<string>  $copied
     */
    private function readme(string $basis, array $copied, bool $includedBuild): string
    {
        $laravelFiles = [];
        $publicFiles = [];

        foreach ($copied as $relative) {
            $package = FtpDeployLayout::packageRelativePath($relative);
            if ($package === null) {
                continue;
            }
            if (str_starts_with($package, 'laravel/')) {
                $laravelFiles[] = substr($package, strlen('laravel/'));
            } elseif (str_starts_with($package, 'public_html/')) {
                $publicFiles[] = substr($package, strlen('public_html/'));
            }
        }

        $laravelList = $laravelFiles === []
            ? "  (nenhum)\n"
            : implode("\n", array_map(fn (string $f): string => '  - '.$f, $laravelFiles))."\n";

        $publicList = $publicFiles === []
            ? "  (nenhum)\n"
            : implode("\n", array_map(fn (string $f): string => '  - '.$f, $publicFiles))."\n";

        $buildNote = $includedBuild
            ? "Sim — envie a pasta build inteira (substitua public_html/build no servidor).\n"
            : "Nao — assets Vite nao entraram neste delta.\n";

        $composerNote = in_array('composer.lock', $copied, true) || in_array('composer.json', $copied, true)
            ? "ATENCAO: composer.json/lock mudaram. Prefira reenviar vendor/ do pacote completo (composer no servidor pode falhar).\n"
            : "composer.json/lock nao mudaram neste delta.\n";

        $migrationNote = $this->hasMigrations($copied)
            ? "Ha migrations neste pacote. No SSH:\n  cd ~/domains/ccb.tinexus.com.br/laravel\n  /opt/alt/php85/usr/bin/php artisan migrate --force\n  /opt/alt/php85/usr/bin/php artisan nexus:seed-catalog\n"
            : "Sem migrations novas neste delta.\n";

        $configNote = $this->hasConfigChanges($copied)
            ? "Ha mudancas em config/. No SSH apos o upload:\n  cd ~/domains/ccb.tinexus.com.br/laravel\n  /opt/alt/php85/usr/bin/php artisan optimize:clear\n  /opt/alt/php85/usr/bin/php artisan optimize\n"
            : '';

        return <<<TXT
CCB Nexus — pacote FTP INCREMENTAL (so o que mudou)
Dominio: https://ccb.tinexus.com.br

Base da comparacao
------------------
{$basis}

Este pacote NAO substitui o site inteiro. Envie apenas os arquivos listados,
mantendo a mesma hierarquia laravel/ e public_html/ no servidor.

Como enviar (FileZilla)
-----------------------
1. Entre na RAIZ do dominio ccb.tinexus.com.br (ao lado de public_html).
2. Envie os arquivos de "laravel/" deste pacote para ~/domains/ccb.tinexus.com.br/laravel/
   (mesmos caminhos relativos — sobrescreva os existentes).
3. Envie o CONTEUDO de "public_html/" deste pacote para dentro do public_html
   do servidor (mesmos caminhos relativos).
4. Nao apague pastas no servidor so porque nao vieram neste delta.

Assets Vite (public_html/build)
-------------------------------
{$buildNote}
Composer / vendor
-----------------
{$composerNote}
Migrations / Artisan
--------------------
{$migrationNote}{$configNote}
PHP CLI neste host: /opt/alt/php85/usr/bin/php

Arquivos em laravel/
--------------------
{$laravelList}
Arquivos em public_html/
------------------------
{$publicList}
Apos um prepare completo, o projeto grava:
  .ftp-last-deploy          <- commit/ref do ultimo pacote cheio
  .ftp-deploy-manifest.json <- hashes dos arquivos deployaveis

Proximos deltas:
  php artisan ftp:push --delta
  php artisan ftp:delta --force
  php artisan ftp:delta --force --since=HEAD~5
  php artisan ftp:delta --force --since-mtime="2026-10-05 10:00"
  php artisan ftp:delta --force --from-manifest

TXT;
    }

    /**
     * @param  list<string>  $copied
     */
    private function hasMigrations(array $copied): bool
    {
        foreach ($copied as $path) {
            if (str_starts_with(str_replace('\\', '/', $path), 'database/migrations/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $copied
     */
    private function hasConfigChanges(array $copied): bool
    {
        foreach ($copied as $path) {
            if (str_starts_with(str_replace('\\', '/', $path), 'config/')) {
                return true;
            }
        }

        return false;
    }

    private function writeDeployMarkers(Filesystem $files): void
    {
        $payload = [
            'prepared_at' => now()->toIso8601String(),
            'commit' => $this->currentGitCommit(),
            'source' => 'ftp:delta',
        ];

        $files->put(FtpDeployLayout::markerPath(), json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
        $files->put(FtpDeployLayout::manifestPath(), json_encode([
            ...$payload,
            'files' => $this->scanCurrentHashes($files),
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
}
