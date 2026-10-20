<?php

namespace App\Support;

use RuntimeException;

/**
 * Upload FTP via cURL (sem ext-ftp), com criacao de pastas em lote e envio paralelo.
 *
 * Hostinger: o FTP abre DENTRO de public_html.
 * Com DEPLOY_FTP_REMOTE_ROOT=.. TODO upload comeca com "CWD ..".
 */
final class FtpCurlUploader
{
    private string $host;

    private string $username;

    private string $password;

    private int $port;

    private bool $ssl;

    private bool $passive;

    private int $timeout;

    private string $remoteRoot;

    private int $concurrency;

    /** @var array<string, true> */
    private array $ensuredDirectories = [];

    /**
     * @param  array{
     *     host?: string|null,
     *     username?: string|null,
     *     password?: string|null,
     *     port?: int,
     *     ssl?: bool,
     *     passive?: bool,
     *     timeout?: int,
     *     remote_root?: string,
     *     concurrency?: int
     * }  $config
     */
    public function __construct(array $config)
    {
        $this->host = $this->normalizeHost((string) ($config['host'] ?? ''));
        $this->username = trim((string) ($config['username'] ?? ''));
        $this->password = (string) ($config['password'] ?? '');
        $this->port = (int) ($config['port'] ?? 21);
        $this->ssl = (bool) ($config['ssl'] ?? true);
        $this->passive = (bool) ($config['passive'] ?? true);
        $this->timeout = max(30, (int) ($config['timeout'] ?? 120));
        $this->remoteRoot = $this->normalizeRemoteRoot((string) ($config['remote_root'] ?? '..'));
        $this->concurrency = max(1, min(16, (int) ($config['concurrency'] ?? 8)));
    }

    public function assertConfigured(): void
    {
        if ($this->host === '' || $this->username === '' || $this->password === '') {
            throw new RuntimeException(
                'Configure DEPLOY_FTP_HOST, DEPLOY_FTP_USERNAME e DEPLOY_FTP_PASSWORD no .env.'
            );
        }

        if (! function_exists('curl_init') || ! function_exists('curl_multi_init')) {
            throw new RuntimeException('Extensao cURL do PHP e obrigatoria para ftp:push.');
        }
    }

    public function concurrency(): int
    {
        return $this->concurrency;
    }

    public function withConcurrency(int $concurrency): self
    {
        $clone = clone $this;
        $clone->concurrency = max(1, min(16, $concurrency));

        return $clone;
    }

    /**
     * @param  string  $remoteRelativePath  Ex.: laravel/artisan ou public_html/index.php
     */
    public function uploadFile(string $localAbsolutePath, string $remoteRelativePath): void
    {
        $failures = $this->uploadMany([
            ['local' => $localAbsolutePath, 'remote' => $remoteRelativePath],
        ], concurrency: 1);

        if ($failures !== []) {
            throw new RuntimeException($failures[0]);
        }
    }

    /**
     * @param  list<array{local: string, remote: string}>  $files
     * @param  (callable(int $done, int $total, ?string $remote): void)|null  $onProgress
     * @return list<string> falhas legiveis
     */
    public function uploadMany(array $files, ?int $concurrency = null, ?callable $onProgress = null): array
    {
        $this->assertConfigured();

        if ($files === []) {
            return [];
        }

        $concurrency = max(1, min(16, $concurrency ?? $this->concurrency));

        $prepared = [];
        foreach ($files as $file) {
            $local = $file['local'];
            $remote = str_replace('\\', '/', ltrim($file['remote'], '/'));

            if (! is_file($local)) {
                return ['Arquivo local inexistente: '.$local];
            }

            [$cwdChain, $fileName] = $this->navigationFor($remote);
            $prepared[] = [
                'local' => $local,
                'remote' => $remote,
                'cwd' => $cwdChain,
                'file' => $fileName,
                'size' => filesize($local) ?: 0,
            ];
        }

        $this->ensureDirectoriesForPrepared($prepared);

        if ($concurrency === 1) {
            return $this->uploadSequential($prepared, $onProgress);
        }

        return $this->uploadParallel($prepared, $concurrency, $onProgress);
    }

    public function ping(): void
    {
        $this->assertConfigured();
        $this->rawRequest($this->buildUrl(''), $this->rootCommands(), [
            CURLOPT_DIRLISTONLY => true,
            CURLOPT_NOBODY => true,
        ]);
    }

    public function remoteRoot(): string
    {
        return $this->remoteRoot;
    }

    /**
     * @param  list<array{local: string, remote: string, cwd: list<string>, file: string, size: int}>  $prepared
     * @param  (callable(int $done, int $total, ?string $remote): void)|null  $onProgress
     * @return list<string>
     */
    private function uploadSequential(array $prepared, ?callable $onProgress): array
    {
        $failed = [];
        $total = count($prepared);
        $done = 0;

        foreach ($prepared as $item) {
            try {
                $this->transferOne($item);
            } catch (RuntimeException $e) {
                $failed[] = $item['remote'].' — '.$e->getMessage();
            }
            $done++;
            if ($onProgress) {
                $onProgress($done, $total, $item['remote']);
            }
        }

        return $failed;
    }

    /**
     * @param  list<array{local: string, remote: string, cwd: list<string>, file: string, size: int}>  $prepared
     * @param  (callable(int $done, int $total, ?string $remote): void)|null  $onProgress
     * @return list<string>
     */
    private function uploadParallel(array $prepared, int $concurrency, ?callable $onProgress): array
    {
        $failed = [];
        $total = count($prepared);
        $done = 0;
        $queue = $prepared;
        $mh = curl_multi_init();
        if ($mh === false) {
            throw new RuntimeException('Falha ao iniciar cURL multi.');
        }

        /** @var array<int, array{ch: \CurlHandle, fh: resource, remote: string}> $active */
        $active = [];

        while ($queue !== [] || $active !== []) {
            while ($queue !== [] && count($active) < $concurrency) {
                $item = array_shift($queue);
                $fh = fopen($item['local'], 'rb');
                if ($fh === false) {
                    $failed[] = $item['remote'].' — Nao foi possivel ler: '.$item['local'];
                    $done++;
                    if ($onProgress) {
                        $onProgress($done, $total, $item['remote']);
                    }

                    continue;
                }

                $ch = $this->makeUploadHandle($item, $fh);
                $id = spl_object_id($ch);
                $active[$id] = ['ch' => $ch, 'fh' => $fh, 'remote' => $item['remote']];
                curl_multi_add_handle($mh, $ch);
            }

            do {
                $status = curl_multi_exec($mh, $running);
            } while ($status === CURLM_CALL_MULTI_PERFORM);

            if ($running > 0) {
                curl_multi_select($mh, 1.0);
            }

            while ($info = curl_multi_info_read($mh)) {
                /** @var \CurlHandle $ch */
                $ch = $info['handle'];
                $id = spl_object_id($ch);
                $meta = $active[$id] ?? null;
                if ($meta === null) {
                    curl_multi_remove_handle($mh, $ch);
                    curl_close($ch);

                    continue;
                }

                $errno = curl_errno($ch);
                $error = curl_error($ch);
                curl_multi_remove_handle($mh, $ch);
                curl_close($ch);
                fclose($meta['fh']);
                unset($active[$id]);

                if ($errno !== 0) {
                    $message = 'FTP falhou ('.$errno.'): '.($error !== '' ? $error : 'erro desconhecido');
                    // Retry serial em falhas transitórias (Hostinger / corrida de CWD).
                    try {
                        $this->ensureDirectoryChain($this->navigationFor($meta['remote'])[0]);
                        $retryLocal = null;
                        foreach ($prepared as $candidate) {
                            if ($candidate['remote'] === $meta['remote']) {
                                $retryLocal = $candidate;
                                break;
                            }
                        }
                        if ($retryLocal !== null) {
                            $this->transferOne($retryLocal);
                        } else {
                            $failed[] = $meta['remote'].' — '.$message;
                        }
                    } catch (RuntimeException $e) {
                        $failed[] = $meta['remote'].' — '.$message.' | retry: '.$e->getMessage();
                    }
                }

                $done++;
                if ($onProgress) {
                    $onProgress($done, $total, $meta['remote']);
                }
            }
        }

        curl_multi_close($mh);

        return $failed;
    }

    /**
     * @param  array{local: string, remote: string, cwd: list<string>, file: string, size: int}  $item
     * @param  resource  $fh
     */
    private function makeUploadHandle(array $item, $fh): \CurlHandle
    {
        $ch = curl_init($this->buildUrl($item['file']));
        if ($ch === false) {
            throw new RuntimeException('Falha ao iniciar cURL.');
        }

        curl_setopt_array($ch, $this->baseCurlOptions($item['cwd']) + [
            CURLOPT_UPLOAD => true,
            CURLOPT_INFILE => $fh,
            CURLOPT_INFILESIZE => $item['size'],
        ]);

        return $ch;
    }

    /**
     * @param  array{local: string, remote: string, cwd: list<string>, file: string, size: int}  $item
     */
    private function transferOne(array $item): void
    {
        $fh = fopen($item['local'], 'rb');
        if ($fh === false) {
            throw new RuntimeException('Nao foi possivel ler: '.$item['local']);
        }

        try {
            $this->rawRequest($this->buildUrl($item['file']), $item['cwd'], [
                CURLOPT_UPLOAD => true,
                CURLOPT_INFILE => $fh,
                CURLOPT_INFILESIZE => $item['size'],
            ]);
        } finally {
            fclose($fh);
        }
    }

    /**
     * @param  list<array{cwd: list<string>}>  $prepared
     */
    private function ensureDirectoriesForPrepared(array $prepared): void
    {
        $chains = [];
        foreach ($prepared as $item) {
            $chains[implode("\n", $item['cwd'])] = $item['cwd'];
        }

        // Pastas mais rasas primeiro.
        uasort($chains, fn (array $a, array $b): int => count($a) <=> count($b));

        foreach ($chains as $cwdChain) {
            $this->ensureDirectoryChain($cwdChain);
        }
    }

    /**
     * @return array{0: list<string>, 1: string}
     */
    private function navigationFor(string $packageRelative): array
    {
        $parts = $this->splitPath($packageRelative);
        if ($parts === []) {
            throw new RuntimeException('Caminho remoto invalido: '.$packageRelative);
        }

        $fileName = array_pop($parts);
        $cwd = $this->rootCommands();
        foreach ($parts as $segment) {
            $cwd[] = 'CWD '.$segment;
        }

        return [$cwd, $fileName];
    }

    /**
     * @return list<string>
     */
    private function rootCommands(): array
    {
        if ($this->usesParentOfLoginDir()) {
            return ['CWD ..'];
        }

        $commands = [];
        foreach ($this->splitPath($this->remoteRoot) as $segment) {
            $commands[] = 'CWD '.$segment;
        }

        return $commands;
    }

    /**
     * @param  list<string>  $cwdChain
     */
    private function ensureDirectoryChain(array $cwdChain): void
    {
        $key = implode("\n", $cwdChain);
        if ($key !== '' && isset($this->ensuredDirectories[$key])) {
            return;
        }

        $prefix = [];
        foreach ($cwdChain as $command) {
            if (! str_starts_with($command, 'CWD ')) {
                continue;
            }

            $segment = substr($command, 4);
            if ($segment === '..') {
                $prefix[] = $command;

                continue;
            }

            $mkdirKey = implode("\n", array_merge($prefix, ['MKD '.$segment]));
            if (! isset($this->ensuredDirectories[$mkdirKey])) {
                $this->rawRequest(
                    $this->buildUrl(''),
                    array_merge($prefix, ['MKD '.$segment]),
                    [CURLOPT_NOBODY => true],
                    ignoreQuoteErrors: true,
                );
                // Confirma que da para entrar na pasta (evita corrida no upload paralelo).
                $this->rawRequest(
                    $this->buildUrl(''),
                    array_merge($prefix, ['CWD '.$segment]),
                    [CURLOPT_DIRLISTONLY => true, CURLOPT_NOBODY => true],
                );
                $this->ensuredDirectories[$mkdirKey] = true;
            }

            $prefix[] = $command;
        }

        if ($key !== '') {
            $this->ensuredDirectories[$key] = true;
        }
    }

    /**
     * @param  list<string>  $prequote
     * @return array<int, mixed>
     */
    private function baseCurlOptions(array $prequote): array
    {
        $options = [
            CURLOPT_USERPWD => $this->username.':'.$this->password,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => min(30, $this->timeout),
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_FTP_USE_EPSV => $this->passive,
            CURLOPT_FTP_USE_EPRT => $this->passive,
            CURLOPT_FTP_FILEMETHOD => CURLFTPMETHOD_NOCWD,
        ];

        if (defined('CURLOPT_TCP_KEEPALIVE')) {
            $options[CURLOPT_TCP_KEEPALIVE] = 1;
        }

        if ($prequote !== []) {
            $options[CURLOPT_PREQUOTE] = $prequote;
        }

        if ($this->ssl) {
            $options[CURLOPT_USE_SSL] = CURLUSESSL_ALL;
            $options[CURLOPT_FTPSSLAUTH] = CURLFTPAUTH_TLS;
            $options[CURLOPT_SSL_VERIFYPEER] = false;
            $options[CURLOPT_SSL_VERIFYHOST] = 0;
        }

        return $options;
    }

    /**
     * @param  list<string>  $prequote
     * @param  array<int, mixed>  $extra
     */
    private function rawRequest(string $url, array $prequote, array $extra = [], bool $ignoreQuoteErrors = false): void
    {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new RuntimeException('Falha ao iniciar cURL.');
        }

        $options = $this->baseCurlOptions($prequote);
        foreach ($extra as $key => $value) {
            if ($key === CURLOPT_PREQUOTE) {
                continue;
            }
            $options[$key] = $value;
        }

        curl_setopt_array($ch, $options);
        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false || $errno !== 0) {
            if ($ignoreQuoteErrors && $this->isIgnorableMkdirError($error)) {
                return;
            }

            $quotes = $prequote === [] ? '' : ' quotes='.implode(' | ', $prequote);

            throw new RuntimeException(
                'FTP falhou ('.$errno.'): '.($error !== '' ? $error : 'erro desconhecido').' ['.$url.']'.$quotes
            );
        }
    }

    private function isIgnorableMkdirError(string $error): bool
    {
        $error = strtolower($error);

        return str_contains($error, 'exists')
            || str_contains($error, 'file exists')
            || str_contains($error, 'already exists')
            || str_contains($error, '550');
    }

    private function buildUrl(string $fileName): string
    {
        $fileName = ltrim(str_replace('\\', '/', $fileName), '/');

        return sprintf('ftp://%s:%d/%s', $this->host, $this->port, $fileName);
    }

    /**
     * @return list<string>
     */
    private function splitPath(string $path): array
    {
        return array_values(array_filter(
            explode('/', str_replace('\\', '/', $path)),
            fn (string $p): bool => $p !== '' && $p !== '.',
        ));
    }

    private function usesParentOfLoginDir(): bool
    {
        return $this->remoteRoot === '..';
    }

    private function normalizeRemoteRoot(string $root): string
    {
        $root = str_replace('\\', '/', trim($root));

        if ($root === '..' || $root === '/..' || $root === '../') {
            return '..';
        }

        if ($root === '' || $root === '/') {
            return '..';
        }

        return '/'.trim($root, '/');
    }

    private function normalizeHost(string $host): string
    {
        $host = trim($host);
        $host = preg_replace('#^(ftps?|sftp)://#i', '', $host) ?? $host;
        $host = rtrim($host, '/');

        // Host com porta embutida (ex.: 147.93.38.181:21) — porta vem de DEPLOY_FTP_PORT.
        if (preg_match('/^(.+):(\d+)$/', $host, $m) && ! str_contains($m[1], ']')) {
            $host = $m[1];
        }

        return trim($host);
    }
}
