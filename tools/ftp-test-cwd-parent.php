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

// Lista apos CWD ..
$ch = curl_init("ftp://{$host}:{$port}/");
curl_setopt_array($ch, [
    CURLOPT_USERPWD => $user.':'.$pass,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_DIRLISTONLY => true,
    CURLOPT_TIMEOUT => 45,
    CURLOPT_FTP_USE_EPSV => true,
    CURLOPT_PREQUOTE => ['CWD ..'],
]);
$body = curl_exec($ch);
echo "LIST AFTER PREQUOTE CWD ..\n";
echo ($body === false ? 'ERR '.curl_errno($ch).' '.curl_error($ch) : trim((string) $body))."\n\n";
curl_close($ch);

// Cria laravel-probe.txt no pai
$tmp = tempnam(sys_get_temp_dir(), 'ftp');
file_put_contents($tmp, "probe-parent\n");
$ch = curl_init("ftp://{$host}:{$port}/laravel-probe-parent.txt");
$fh = fopen($tmp, 'rb');
curl_setopt_array($ch, [
    CURLOPT_USERPWD => $user.':'.$pass,
    CURLOPT_UPLOAD => true,
    CURLOPT_INFILE => $fh,
    CURLOPT_INFILESIZE => filesize($tmp),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 45,
    CURLOPT_FTP_USE_EPSV => true,
    CURLOPT_FTP_CREATE_MISSING_DIRS => CURLFTP_CREATE_DIR_RETRY,
    CURLOPT_PREQUOTE => ['CWD ..'],
]);
$ok = curl_exec($ch);
echo "UPLOAD TO PARENT laravel-probe-parent.txt\n";
echo ($ok === false ? 'ERR '.curl_errno($ch).' '.curl_error($ch) : 'OK')."\n\n";
curl_close($ch);
fclose($fh);

// Confirma: arquivo deve aparecer no pai (ao lado de public_html), nao dentro
$ch = curl_init("ftp://{$host}:{$port}/");
curl_setopt_array($ch, [
    CURLOPT_USERPWD => $user.':'.$pass,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_DIRLISTONLY => true,
    CURLOPT_TIMEOUT => 45,
    CURLOPT_FTP_USE_EPSV => true,
    CURLOPT_PREQUOTE => ['CWD ..'],
]);
$body = curl_exec($ch);
echo "PARENT LIST AFTER UPLOAD\n";
echo ($body === false ? 'ERR' : trim((string) $body))."\n\n";
curl_close($ch);

$ch = curl_init("ftp://{$host}:{$port}/");
curl_setopt_array($ch, [
    CURLOPT_USERPWD => $user.':'.$pass,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_DIRLISTONLY => true,
    CURLOPT_TIMEOUT => 45,
    CURLOPT_FTP_USE_EPSV => true,
]);
$body = curl_exec($ch);
echo "PUBLIC_HTML (login dir) LIST\n";
echo ($body === false ? 'ERR' : trim((string) $body))."\n";
curl_close($ch);
@unlink($tmp);
