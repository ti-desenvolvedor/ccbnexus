<?php

namespace App\Console\Commands;

use App\Support\FtpCurlUploader;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Throwable;

class PushFtpDeployCommand extends Command
{
    protected $signature = 'ftp:push
                            {--path=ftp-deploy : Pasta local do pacote (ftp-deploy ou ftp-deploy-delta)}
                            {--prepare : Roda ftp:prepare --force antes do envio}
                            {--delta : Roda ftp:delta --force antes do envio}
                            {--dry-run : Lista arquivos sem enviar}
                            {--skip-vendor : Nao envia laravel/vendor (use se vendor ja estiver no servidor)}
                            {--only= : Prefixo relativo a enviar (ex: laravel/app ou public_html/build)}
                            {--concurrency= : Uploads paralelos (1-16; padrao DEPLOY_FTP_CONCURRENCY)}
                            {--remote : Apos o upload, roda migrate --force, nexus:seed-catalog e optimize via SSH}
                            {--remote-migrate : Alias de --remote (migrate + seed + optimize via SSH)}
                            {--no-maintenance : Nao coloca o site em manutencao durante o envio}';

    protected $description = 'Envia o pacote FTP (laravel/ + public_html/) para a Hostinger via FTPS/cURL.';

    public function handle(Filesystem $files): int
    {
        if ($this->option('prepare') && $this->option('delta')) {
            $this->error('Use apenas uma opcao: --prepare ou --delta.');

            return self::FAILURE;
        }

        if ($this->option('prepare')) {
            $code = $this->call('ftp:prepare', ['--force' => true]);
            if ($code !== self::SUCCESS) {
                return $code;
            }
        }

        if ($this->option('delta')) {
            $code = $this->call('ftp:delta', ['--force' => true]);
            if ($code !== self::SUCCESS) {
                return $code;
            }
            if (! $this->option('path') || $this->option('path') === 'ftp-deploy') {
                $this->input->setOption('path', 'ftp-deploy-delta');
            }
        }

        $destination = $this->resolveDestination();
        if (! $files->isDirectory($destination)) {
            $this->error("Pasta nao encontrada: {$destination}. Rode ftp:prepare --force (ou use --prepare).");

            return self::FAILURE;
        }

        $laravel = $destination.DIRECTORY_SEPARATOR.'laravel';
        $publicHtml = $destination.DIRECTORY_SEPARATOR.'public_html';

        if (! $files->isDirectory($laravel) && ! $files->isDirectory($publicHtml)) {
            $this->error('O pacote precisa ter laravel/ e/ou public_html/.');

            return self::FAILURE;
        }

        $concurrency = $this->resolveConcurrency();

        try {
            $uploader = new FtpCurlUploader((array) config('deploy.ftp'));
            $uploader = $uploader->withConcurrency($concurrency);
            if (! $this->option('dry-run')) {
                $uploader->assertConfigured();
                $this->info('Testando conexao FTP...');
                $uploader->ping();
                $this->info('Conexao OK. remote_root='.$uploader->remoteRoot().' concurrency='.$uploader->concurrency());
                if (in_array($uploader->remoteRoot(), ['..', '/..'], true)) {
                    $this->line('Destino: um nivel acima do diretorio inicial do FTP (laravel/ ao lado de public_html/).');
                }
            }
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $plan = $this->collectUploadPlan($files, $destination);
        if ($plan === []) {
            $this->warn('Nenhum arquivo para enviar.');

            return self::SUCCESS;
        }

        $this->info('Arquivos a enviar: '.count($plan).' (paralelismo: '.$concurrency.')');
        if ($this->option('dry-run')) {
            if (! $this->option('no-maintenance')) {
                $this->line('Manutencao: artisan down antes do envio e artisan up no fim.');
            }
            foreach (array_slice($plan, 0, 40) as $item) {
                $this->line('  '.$item['remote']);
            }
            if (count($plan) > 40) {
                $this->line('  ... +'.(count($plan) - 40).' arquivos');
            }

            return self::SUCCESS;
        }

        $maintenanceOn = false;
        $exitCode = self::SUCCESS;

        try {
            if (! $this->option('no-maintenance')) {
                $maintenanceOn = $this->enterMaintenance();
            }

            $bar = $this->output->createProgressBar(count($plan));
            $bar->start();

            $failed = $uploader->uploadMany(
                $plan,
                $concurrency,
                function () use ($bar): void {
                    $bar->advance();
                },
            );

            $bar->finish();
            $this->newLine(2);

            if ($failed !== []) {
                $this->error('Falhas: '.count($failed).' / '.count($plan));
                foreach (array_slice($failed, 0, 20) as $line) {
                    $this->line('  '.$line);
                }

                $exitCode = self::FAILURE;
            } else {
                $this->info('Upload concluido: '.count($plan).' arquivos.');

                if ($this->option('remote') || $this->option('remote-migrate')) {
                    foreach (['migrate', 'seed', 'optimize'] as $preset) {
                        $code = $this->call('ssh:run', ['preset' => $preset]);
                        if ($code !== self::SUCCESS) {
                            $exitCode = $code;
                            break;
                        }
                    }
                } else {
                    $sshPath = rtrim((string) config('deploy.ssh_path', 'domains/ccb.tinexus.com.br'), '/');
                    $phpCli = (string) config('deploy.php_cli', '/opt/alt/php85/usr/bin/php');
                    $this->line('No SSH (ou daqui: php artisan ftp:push --delta --remote):');
                    $this->line('  cd ~/'.$sshPath.'/laravel');
                    $this->line('  '.$phpCli.' artisan migrate --force');
                    $this->line('  '.$phpCli.' artisan nexus:seed-catalog');
                    $this->line('  '.$phpCli.' artisan optimize:clear');
                    $this->line('  '.$phpCli.' artisan optimize');
                    $this->line('Atalho pos-upload: php artisan ftp:push --delta --remote');
                }
            }
        } finally {
            if ($maintenanceOn) {
                $this->leaveMaintenance();
            }
        }

        return $exitCode;
    }

    private function enterMaintenance(): bool
    {
        $this->info('Colocando o site em manutencao (artisan down)...');
        $code = $this->call('ssh:run', ['preset' => 'down']);

        if ($code !== self::SUCCESS) {
            $this->warn('Nao foi possivel ativar manutencao via SSH. O upload continua — confira o site depois.');

            return false;
        }

        $this->info('Site em manutencao.');

        return true;
    }

    private function leaveMaintenance(): void
    {
        $this->info('Tirando o site de manutencao (artisan up)...');
        $code = $this->call('ssh:run', ['preset' => 'up']);

        if ($code !== self::SUCCESS) {
            $this->error('Falha ao sair de manutencao. Rode: php artisan ssh:run up');

            return;
        }

        $this->info('Site no ar novamente.');
    }

    private function resolveConcurrency(): int
    {
        $option = $this->option('concurrency');
        if ($option !== null && $option !== '') {
            return max(1, min(16, (int) $option));
        }

        return max(1, min(16, (int) config('deploy.ftp.concurrency', 8)));
    }

    /**
     * @return list<array{local: string, remote: string}>
     */
    private function collectUploadPlan(Filesystem $files, string $destination): array
    {
        $only = trim((string) $this->option('only'));
        $only = $only !== '' ? str_replace('\\', '/', ltrim($only, '/')) : null;
        $skipVendor = (bool) $this->option('skip-vendor');
        $plan = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($destination, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY,
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->isLink()) {
                continue;
            }

            $absolute = $file->getPathname();
            $relative = str_replace('\\', '/', substr($absolute, strlen($destination) + 1));

            if ($relative === 'LEIA-ME.txt') {
                continue;
            }

            if (! str_starts_with($relative, 'laravel/') && ! str_starts_with($relative, 'public_html/')) {
                continue;
            }

            if ($skipVendor && str_starts_with($relative, 'laravel/vendor/')) {
                continue;
            }

            if ($only !== null && $relative !== $only && ! str_starts_with($relative, rtrim($only, '/').'/')) {
                continue;
            }

            $plan[] = [
                'local' => $absolute,
                'remote' => $relative,
            ];
        }

        // public_html primeiro (site fica no ar), depois laravel/ (app), vendor por ultimo.
        usort($plan, function (array $a, array $b): int {
            $rank = function (string $remote): int {
                if (str_starts_with($remote, 'public_html/')) {
                    return 0;
                }
                if (str_starts_with($remote, 'laravel/vendor/')) {
                    return 2;
                }
                if (str_starts_with($remote, 'laravel/')) {
                    return 1;
                }

                return 3;
            };

            $ra = $rank($a['remote']);
            $rb = $rank($b['remote']);
            if ($ra !== $rb) {
                return $ra <=> $rb;
            }

            return strcmp($a['remote'], $b['remote']);
        });

        return $plan;
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
}
