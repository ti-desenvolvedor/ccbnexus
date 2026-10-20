<?php

use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$cfg = config('deploy.ftp');
$host = $cfg['host'];
$user = $cfg['username'];
$pass = $cfg['password'];
$port = (int) $cfg['port'];

function ftpExec(string $url, string $user, string $pass, array $prequote, array $extra = []): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_USERPWD => $user.':'.$pass,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_FTP_USE_EPSV => true,
        CURLOPT_FTP_FILEMETHOD => CURLFTPMETHOD_NOCWD,
        CURLOPT_PREQUOTE => $prequote,
        ...$extra,
    ]);
    $body = curl_exec($ch);
    $result = [
        'ok' => $body !== false && curl_errno($ch) === 0,
        'errno' => curl_errno($ch),
        'error' => curl_error($ch),
        'body' => is_string($body) ? $body : '',
    ];
    curl_close($ch);

    return $result;
}

function listNames(string $host, int $port, string $user, string $pass, array $prequote): array
{
    $r = ftpExec("ftp://{$host}:{$port}/", $user, $pass, $prequote, [
        CURLOPT_DIRLISTONLY => true,
    ]);
    if (! $r['ok']) {
        return [];
    }

    return array_values(array_filter(preg_split("/\r\n|\n|\r/", trim($r['body'])) ?: [], function (string $n): bool {
        return $n !== '' && $n !== '.' && $n !== '..';
    }));
}

/** Remove arquivo ou pasta (recursivo simples). */
function deletePath(string $host, int $port, string $user, string $pass, array $cwd, string $name, bool $isDirHint = false): void
{
    $pre = $cwd;
    if ($isDirHint) {
        // tenta esvaziar
        $childCwd = array_merge($cwd, ['CWD '.$name]);
        foreach (listNames($host, $port, $user, $pass, $childCwd) as $child) {
            // heuristica: se listar como dir falhar no DELE, tenta recursivo
            $dele = ftpExec("ftp://{$host}:{$port}/{$child}", $user, $pass, $childCwd, [
                CURLOPT_QUOTE => ['DELE '.$child],
                CURLOPT_NOBODY => true,
            ]);
            if (! $dele['ok']) {
                deletePath($host, $port, $user, $pass, $childCwd, $child, true);
            }
        }
        ftpExec("ftp://{$host}:{$port}/", $user, $pass, $cwd, [
            CURLOPT_QUOTE => ['RMD '.$name],
            CURLOPT_NOBODY => true,
        ]);

        return;
    }

    $dele = ftpExec("ftp://{$host}:{$port}/{$name}", $user, $pass, $cwd, [
        CURLOPT_QUOTE => ['DELE '.$name],
        CURLOPT_NOBODY => true,
    ]);
    if (! $dele['ok']) {
        deletePath($host, $port, $user, $pass, $cwd, $name, true);
    }
}

echo "Limpando lixo do deploy errado dentro de public_html (login)...\n";
$loginJunk = [
    'laravel', // pasta errada dentro de public_html
    '_layout_check.txt',
    '_ftp_parent_check.txt',
    '_rel_dot.txt',
    '_rel_abs.txt',
    '_rel_plain.txt',
    'app',
    'ccb-laravel',
    'testmkdir123',
    'testmkdir_in_public',
];

foreach ($loginJunk as $name) {
    echo " - login/{$name}\n";
    deletePath($host, $port, $user, $pass, [], $name, $name === 'laravel' || in_array($name, ['app', 'ccb-laravel', 'testmkdir123', 'testmkdir_in_public'], true));
}

echo "Limpando probes no pai...\n";
foreach (['laravel-probe-parent.txt', 'nexus-app'] as $name) {
    echo " - parent/{$name}\n";
    deletePath($host, $port, $user, $pass, ['CWD ..'], $name, $name === 'nexus-app');
}

echo "\nEstado atual:\n";
echo 'PARENT: '.implode(', ', listNames($host, $port, $user, $pass, ['CWD ..']))."\n";
echo 'LOGIN: '.implode(', ', listNames($host, $port, $user, $pass, []))."\n";
echo 'PARENT/laravel: '.implode(', ', listNames($host, $port, $user, $pass, ['CWD ..', 'CWD laravel']))."\n";
