<?php

use Illuminate\Contracts\Console\Kernel;

/**
 * Remove a pasta laravel/ ERRADA que ficou dentro de public_html (login FTP).
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$cfg = config('deploy.ftp');
$host = $cfg['host'];
$user = $cfg['username'];
$pass = $cfg['password'];
$port = (int) $cfg['port'];

function ftpCmd(string $host, int $port, string $user, string $pass, array $prequote, array $extra = []): array
{
    $ch = curl_init("ftp://{$host}:{$port}/");
    $opts = [
        CURLOPT_USERPWD => $user.':'.$pass,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_FTP_USE_EPSV => true,
        CURLOPT_FTP_FILEMETHOD => CURLFTPMETHOD_NOCWD,
    ];
    if ($prequote !== []) {
        $opts[CURLOPT_PREQUOTE] = $prequote;
    }
    foreach ($extra as $k => $v) {
        $opts[$k] = $v;
    }
    curl_setopt_array($ch, $opts);
    $body = curl_exec($ch);
    $out = ['ok' => $body !== false && curl_errno($ch) === 0, 'err' => curl_error($ch), 'body' => (string) $body];
    curl_close($ch);

    return $out;
}

function listDir(string $host, int $port, string $user, string $pass, array $cwd): array
{
    $r = ftpCmd($host, $port, $user, $pass, $cwd, [CURLOPT_DIRLISTONLY => true]);
    if (! $r['ok']) {
        return [];
    }

    return array_values(array_filter(preg_split("/\r\n|\n|\r/", trim($r['body'])) ?: [], fn ($n) => $n !== '' && $n !== '.' && $n !== '..'));
}

function rmTree(string $host, int $port, string $user, string $pass, array $cwd, string $name): void
{
    $inside = array_merge($cwd, ['CWD '.$name]);
    foreach (listDir($host, $port, $user, $pass, $inside) as $child) {
        // tenta arquivo; se falhar, trata como pasta
        $del = ftpCmd($host, $port, $user, $pass, $inside, [
            CURLOPT_NOBODY => true,
            CURLOPT_QUOTE => ['DELE '.$child],
        ]);
        if (! $del['ok']) {
            rmTree($host, $port, $user, $pass, $inside, $child);
        } else {
            echo '  DELE '.implode('/', array_merge($cwd, [$name, $child]))."\n";
        }
    }

    $rm = ftpCmd($host, $port, $user, $pass, $cwd, [
        CURLOPT_NOBODY => true,
        CURLOPT_QUOTE => ['RMD '.$name],
    ]);
    echo ($rm['ok'] ? '  RMD ' : '  RMD FAIL ').implode('/', array_merge($cwd, [$name])).($rm['ok'] ? '' : ' '.$rm['err'])."\n";
}

echo "Removendo public_html/laravel (errado)...\n";
rmTree($host, $port, $user, $pass, [], 'laravel');

// probes
foreach (['_smoke_public.txt', '_layout_check.txt', '_ftp_parent_check.txt', '_rel_dot.txt', '_rel_abs.txt', '_rel_plain.txt', 'testmkdir_in_public'] as $f) {
    $r = ftpCmd($host, $port, $user, $pass, [], [
        CURLOPT_NOBODY => true,
        CURLOPT_QUOTE => ['DELE '.$f],
    ]);
    if ($r['ok']) {
        echo "  DELE {$f}\n";
    }
}

echo "\nLOGIN agora: ".implode(', ', listDir($host, $port, $user, $pass, []))."\n";
echo 'PAI agora: '.implode(', ', listDir($host, $port, $user, $pass, ['CWD ..']))."\n";
