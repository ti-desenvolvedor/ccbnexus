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

$tmp = tempnam(sys_get_temp_dir(), 'ftp');
file_put_contents($tmp, "beside-public-html\n");

// 1) MKD laravel no pai
$ch = curl_init("ftp://{$host}:{$port}/");
curl_setopt_array($ch, [
    CURLOPT_USERPWD => $user.':'.$pass,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_NOBODY => true,
    CURLOPT_TIMEOUT => 45,
    CURLOPT_FTP_USE_EPSV => true,
    CURLOPT_PREQUOTE => ['CWD ..'],
    CURLOPT_QUOTE => ['MKD laravel'],
]);
curl_exec($ch);
echo 'MKD laravel: '.(curl_errno($ch) ? curl_error($ch) : 'ok/exists')."\n";
curl_close($ch);

// 2) Upload usando CWD .. + CWD laravel + arquivo na raiz dessa pasta
$fh = fopen($tmp, 'rb');
$ch = curl_init("ftp://{$host}:{$port}/_beside_ok.txt");
curl_setopt_array($ch, [
    CURLOPT_USERPWD => $user.':'.$pass,
    CURLOPT_UPLOAD => true,
    CURLOPT_INFILE => $fh,
    CURLOPT_INFILESIZE => filesize($tmp),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 60,
    CURLOPT_FTP_USE_EPSV => true,
    CURLOPT_PREQUOTE => ['CWD ..', 'CWD laravel'],
]);
$ok = curl_exec($ch);
echo 'UPLOAD: '.($ok === false ? 'ERR '.curl_errno($ch).' '.curl_error($ch) : 'OK')."\n";
curl_close($ch);
fclose($fh);

// 3) Listar pai
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

// 4) Listar pai/laravel
$ch = curl_init("ftp://{$host}:{$port}/");
curl_setopt_array($ch, [
    CURLOPT_USERPWD => $user.':'.$pass,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_DIRLISTONLY => true,
    CURLOPT_TIMEOUT => 45,
    CURLOPT_FTP_USE_EPSV => true,
    CURLOPT_PREQUOTE => ['CWD ..', 'CWD laravel'],
]);
echo "\nPARENT/laravel:\n".trim((string) curl_exec($ch))."\n";
curl_close($ch);

// 5) Listar login (public_html) — nao deve ter _beside_ok.txt na raiz
$ch = curl_init("ftp://{$host}:{$port}/");
curl_setopt_array($ch, [
    CURLOPT_USERPWD => $user.':'.$pass,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_DIRLISTONLY => true,
    CURLOPT_TIMEOUT => 45,
    CURLOPT_FTP_USE_EPSV => true,
]);
echo "\nLOGIN public_html:\n".trim((string) curl_exec($ch))."\n";
curl_close($ch);
@unlink($tmp);
