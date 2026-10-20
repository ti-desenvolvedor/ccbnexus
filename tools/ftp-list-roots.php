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

$list = function (string $path) use ($host, $user, $pass, $port): void {
    $url = sprintf('ftp://%s:%d%s', $host, $port, $path);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_USERPWD => $user.':'.$pass,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_DIRLISTONLY => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_FTP_USE_EPSV => true,
    ]);
    $body = curl_exec($ch);
    $err = curl_error($ch);
    $errno = curl_errno($ch);
    curl_close($ch);

    echo "PATH={$path}\n";
    if ($body === false) {
        echo "ERR={$errno} {$err}\n---\n";

        return;
    }
    echo trim((string) $body)."\n---\n";
};

$list('/');
$list('/public_html/');
$list('/../');
$list('/../public_html/');
$list('/../laravel/');
