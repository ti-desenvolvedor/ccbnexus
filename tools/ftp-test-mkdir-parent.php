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

foreach (['laravel', 'ccb-laravel', 'app', 'testmkdir123'] as $dir) {
    $ch = curl_init("ftp://{$host}:{$port}/");
    curl_setopt_array($ch, [
        CURLOPT_USERPWD => $user.':'.$pass,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_VERBOSE => false,
        CURLOPT_NOBODY => true,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_FTP_USE_EPSV => true,
        CURLOPT_PREQUOTE => ['CWD ..'],
        CURLOPT_QUOTE => ['MKD '.$dir],
    ]);
    $ok = curl_exec($ch);
    echo "MKD {$dir}: errno=".curl_errno($ch).' err='.curl_error($ch)."\n";
    curl_close($ch);
}

$ch = curl_init("ftp://{$host}:{$port}/");
curl_setopt_array($ch, [
    CURLOPT_USERPWD => $user.':'.$pass,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_DIRLISTONLY => true,
    CURLOPT_TIMEOUT => 45,
    CURLOPT_FTP_USE_EPSV => true,
    CURLOPT_PREQUOTE => ['CWD ..'],
]);
echo "\nPARENT:\n".trim((string) curl_exec($ch))."\n";
curl_close($ch);

// Pode criar pasta DENTRO de public_html?
$ch = curl_init("ftp://{$host}:{$port}/");
curl_setopt_array($ch, [
    CURLOPT_USERPWD => $user.':'.$pass,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_NOBODY => true,
    CURLOPT_TIMEOUT => 45,
    CURLOPT_FTP_USE_EPSV => true,
    CURLOPT_QUOTE => ['MKD testmkdir_in_public'],
]);
curl_exec($ch);
echo "\nMKD in login dir: errno=".curl_errno($ch).' err='.curl_error($ch)."\n";
curl_close($ch);

$ch = curl_init("ftp://{$host}:{$port}/");
curl_setopt_array($ch, [
    CURLOPT_USERPWD => $user.':'.$pass,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_DIRLISTONLY => true,
    CURLOPT_TIMEOUT => 45,
    CURLOPT_FTP_USE_EPSV => true,
]);
echo "LOGIN:\n".trim((string) curl_exec($ch))."\n";
curl_close($ch);
