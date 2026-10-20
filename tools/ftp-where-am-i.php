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

$list = function (string $label, array $prequote) use ($host, $user, $pass, $port): void {
    $ch = curl_init("ftp://{$host}:{$port}/");
    curl_setopt_array($ch, [
        CURLOPT_USERPWD => $user.':'.$pass,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_DIRLISTONLY => true,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_FTP_USE_EPSV => true,
        CURLOPT_FTP_FILEMETHOD => CURLFTPMETHOD_NOCWD,
        CURLOPT_PREQUOTE => $prequote,
    ]);
    $body = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    echo "=== {$label} ===\n";
    echo ($body === false ? "ERR {$err}" : trim((string) $body))."\n\n";
};

echo "Se o login ja for a pasta da foto (com public_html), NAO devemos dar CWD ..\n\n";

$list('A) Login sem CWD (diretorio inicial do FTP)', []);
$list('B) Apos CWD .. (um nivel acima)', ['CWD ..']);
$list('C) Login/public_html', ['CWD public_html']);
$list('D) Apos CWD .. / laravel', ['CWD ..', 'CWD laravel']);
$list('E) Login/laravel (errado se existir)', ['CWD laravel']);
