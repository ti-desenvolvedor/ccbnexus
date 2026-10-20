<?php

namespace App\Console\Commands;

use App\Support\DeploySshClient;
use Illuminate\Console\Command;
use Throwable;

class RunSshDeployCommand extends Command
{
    protected $signature = 'ssh:run
    {preset? : Preset (ping|optimize|migrate|seed|about|down|up) ou deixe vazio com --cmd}
    {--cmd= : Comando shell remoto livre (ex: "ls -la")}
    {--artisan=* : Argumentos artisan remotos (ex: --artisan=route:list)}
    {--dry-run : So mostra o comando, nao executa}
';

    protected $description = 'Executa comandos no servidor Hostinger via SSH (optimize, migrate, etc.).';

    /**
     * @var array<string, list<list<string>>>
     */
    private const PRESETS = [
        'ping' => [],
        'optimize' => [
            ['optimize:clear'],
            ['optimize'],
        ],
        'migrate' => [
            ['migrate', '--force'],
        ],
        'seed' => [
            ['nexus:seed-catalog'],
        ],
        'about' => [
            ['about'],
        ],
        'down' => [
            [
                'down',
                '--retry=60',
                '--refresh=15',
                '--render=errors.503',
            ],
        ],
        'up' => [
            ['up'],
        ],
    ];

    public function handle(): int
    {
        try {
            $client = $this->makeClient();
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $preset = $this->argument('preset');
        $rawCmd = trim((string) $this->option('cmd'));
        /** @var list<string> $artisan */
        $artisan = array_values(array_filter(
            (array) $this->option('artisan'),
            fn ($v) => is_string($v) && $v !== '',
        ));

        if ($preset === null && $rawCmd === '' && $artisan === []) {
            $this->line('Uso:');
            $this->line('  php artisan ssh:run ping');
            $this->line('  php artisan ssh:run down');
            $this->line('  php artisan ssh:run up');
            $this->line('  php artisan ssh:run optimize');
            $this->line('  php artisan ssh:run migrate');
            $this->line('  php artisan ssh:run seed');
            $this->line('  php artisan ssh:run about');
            $this->line('  php artisan ssh:run --artisan=route:list');
            $this->line('  php artisan ssh:run --cmd="ls -la ~/domains/ccb.tinexus.com.br/laravel"');
            $this->newLine();
            $this->line('Deploy com manutencao: php artisan ftp:push --delta --remote');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            foreach ($this->commandsToRun($client, $preset, $rawCmd, $artisan) as $command) {
                $this->line($command);
            }

            return self::SUCCESS;
        }

        try {
            if ($preset === 'ping') {
                $this->info('Testando SSH em '.$client->username().'@'.$client->host().'...');
                $this->line($client->ping());
                $this->info('SSH OK.');

                return self::SUCCESS;
            }

            $failed = false;
            foreach ($this->commandsToRun($client, $preset, $rawCmd, $artisan) as $command) {
                $this->comment('$ '.$command);
                $output = $client->exec($command);
                if ($output !== '') {
                    $this->line($output);
                }
                // Artisan remoto costuma imprimir "ERROR" sem falhar o canal SSH.
                if (preg_match('/\bERROR\b/i', $output) === 1) {
                    $failed = true;
                }
            }

            if ($failed) {
                $this->error('Comando remoto reportou ERROR.');

                return self::FAILURE;
            }

            $this->info('Comando(s) remoto(s) concluidos.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error($e->getMessage() !== '' ? $e->getMessage() : $e::class);
            if ($e->getPrevious()) {
                $this->line($e->getPrevious()->getMessage());
            }

            return self::FAILURE;
        } finally {
            $client->disconnect();
        }
    }

    /**
     * @param  list<string>  $artisan
     * @return list<string>
     */
    private function commandsToRun(DeploySshClient $client, ?string $preset, string $rawCmd, array $artisan): array
    {
        if ($rawCmd !== '') {
            return [$rawCmd];
        }

        if ($artisan !== []) {
            return [$client->artisanShell($artisan)];
        }

        if ($preset === null || $preset === 'ping') {
            return ['echo SSH_OK && hostname && '.$client->phpCli().' -v'];
        }

        if (! array_key_exists($preset, self::PRESETS)) {
            throw new \InvalidArgumentException(
                'Preset desconhecido: '.$preset.'. Use: '.implode(', ', array_keys(self::PRESETS)).' ou --cmd / --artisan.'
            );
        }

        $commands = [];
        foreach (self::PRESETS[$preset] as $args) {
            $commands[] = $client->artisanShell($args);
        }

        return $commands;
    }

    private function makeClient(): DeploySshClient
    {
        $ftp = (array) config('deploy.ftp', []);
        $ssh = (array) config('deploy.ssh', []);

        return new DeploySshClient([
            'host' => $ssh['host'] ?: ($ftp['host'] ?? null),
            'username' => $ssh['username'] ?: ($ftp['username'] ?? null),
            'password' => $ssh['password'] !== '' && $ssh['password'] !== null
                ? $ssh['password']
                : ($ftp['password'] ?? null),
            'port' => $ssh['port'] ?? 22,
            'timeout' => $ssh['timeout'] ?? 120,
            'path' => config('deploy.ssh_path'),
            'php_cli' => config('deploy.php_cli'),
        ]);
    }
}
