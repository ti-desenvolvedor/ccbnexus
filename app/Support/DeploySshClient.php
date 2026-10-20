<?php

namespace App\Support;

use phpseclib4\Net\SSH2;
use RuntimeException;
use Throwable;

/**
 * Executa comandos no servidor Hostinger via SSH (senha no .env).
 */
final class DeploySshClient
{
    private string $host;

    private string $username;

    private string $password;

    private int $port;

    private int $timeout;

    private string $sshPath;

    private string $phpCli;

    private ?SSH2 $connection = null;

    /**
     * @param  array{
     *     host?: string|null,
     *     username?: string|null,
     *     password?: string|null,
     *     port?: int,
     *     timeout?: int,
     *     path?: string|null,
     *     php_cli?: string|null
     * }  $config
     */
    public function __construct(array $config)
    {
        $this->host = $this->normalizeHost((string) ($config['host'] ?? ''));
        $this->username = trim((string) ($config['username'] ?? ''));
        $this->password = (string) ($config['password'] ?? '');
        $this->port = (int) ($config['port'] ?? 22);
        $this->timeout = max(15, (int) ($config['timeout'] ?? 120));
        $path = trim((string) ($config['path'] ?? ''), '/');
        $this->sshPath = $path !== '' ? $path : 'domains/ccb.tinexus.com.br';
        $phpCli = trim((string) ($config['php_cli'] ?? ''));
        $this->phpCli = $phpCli !== '' ? $phpCli : '/opt/alt/php85/usr/bin/php';
    }

    public function assertConfigured(): void
    {
        if ($this->host === '' || $this->username === '' || $this->password === '') {
            throw new RuntimeException(
                'Configure DEPLOY_SSH_HOST (ou DEPLOY_FTP_HOST), DEPLOY_SSH_USERNAME e DEPLOY_SSH_PASSWORD no .env.'
            );
        }
    }

    public function host(): string
    {
        return $this->host;
    }

    public function username(): string
    {
        return $this->username;
    }

    public function laravelPath(): string
    {
        return '~/'.$this->sshPath.'/laravel';
    }

    public function phpCli(): string
    {
        return $this->phpCli;
    }

    /**
     * Comando remoto completo para um artisan (com cd na pasta laravel).
     *
     * @param  list<string>  $artisanArgs  ex.: ['optimize:clear']
     */
    public function artisanShell(array $artisanArgs): string
    {
        $quoted = array_map(
            fn (string $arg): string => $this->shellQuote($arg),
            $artisanArgs,
        );

        return sprintf(
            'cd %s && %s artisan %s',
            $this->shellQuote($this->laravelPath()),
            $this->shellQuote($this->phpCli),
            implode(' ', $quoted),
        );
    }

    public function ping(): string
    {
        $output = $this->exec('echo SSH_OK && hostname && '.$this->shellQuote($this->phpCli).' -v');

        if (! str_contains($output, 'SSH_OK')) {
            throw new RuntimeException('SSH respondeu sem confirmacao: '.$output);
        }

        return $output;
    }

    public function runArtisan(array $artisanArgs): string
    {
        return $this->exec($this->artisanShell($artisanArgs));
    }

    public function exec(string $command): string
    {
        $this->assertConfigured();
        $ssh = $this->connect();

        $output = $ssh->exec($command);
        if ($output === null) {
            $errors = method_exists($ssh, 'getErrors') ? implode('; ', $ssh->getErrors()) : '';
            throw new RuntimeException('Falha ao executar SSH'.($errors !== '' ? ': '.$errors : '.'));
        }

        return rtrim($output);
    }

    public function disconnect(): void
    {
        if ($this->connection !== null) {
            $this->connection->disconnect();
            $this->connection = null;
        }
    }

    private function connect(): SSH2
    {
        if ($this->connection !== null) {
            return $this->connection;
        }

        try {
            $ssh = new SSH2($this->host, $this->port, $this->timeout);
            $ssh->setTimeout($this->timeout);

            if (! $ssh->login($this->username, $this->password)) {
                $detail = method_exists($ssh, 'getLastError') ? (string) $ssh->getLastError() : '';
                $errors = method_exists($ssh, 'getErrors') ? implode('; ', $ssh->getErrors()) : '';
                $hint = trim($detail.($errors !== '' ? ' '.$errors : ''));

                throw new RuntimeException(
                    'Login SSH falhou em '.$this->username.'@'.$this->host.':'.$this->port.
                    ($hint !== '' ? ' — '.$hint : '').
                    '. Confira no hPanel → Avancado → SSH Access o host exato e se o SSH esta ativo. A senha pode ser a mesma do FTP.'
                );
            }
        } catch (RuntimeException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new RuntimeException(
                'SSH nao conectou em '.$this->username.'@'.$this->host.':'.$this->port.' — '.$e->getMessage(),
                0,
                $e,
            );
        }

        return $this->connection = $ssh;
    }

    private function shellQuote(string $value): string
    {
        if ($value === '') {
            return "''";
        }

        if (preg_match('/^[A-Za-z0-9_\\/.:=+@%~-]+$/', $value) === 1) {
            return $value;
        }

        return "'".str_replace("'", "'\"'\"'", $value)."'";
    }

    private function normalizeHost(string $host): string
    {
        $host = trim($host);
        $host = preg_replace('#^(ssh|sftp|ftps?)://#i', '', $host) ?? $host;
        $host = rtrim($host, '/');

        if (preg_match('/^(.+):(\d+)$/', $host, $m) && ! str_contains($m[1], ']')) {
            $host = $m[1];
        }

        return trim($host);
    }
}
