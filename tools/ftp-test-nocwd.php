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
file_put_contents($tmp, "nocwd-beside\n");

// MKD via PREQUOTE only + NOCWD
$ch = curl_init("ftp://{$host}:{$port}/");
curl_setopt_array($ch, [
    CURLOPT_USERPWD => $user.':'.$pass,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_NOBODY => true,
    CURLOPT_TIMEOUT => 45,
    CURLOPT_FTP_USE_EPSV => true,
    CURLOPT_FTP_FILEMETHOD => CURLFTPMETHOD_NOCWD,
    CURLOPT_PREQUOTE => ['CWD ..', 'MKD nexus-app'],
]);
curl_exec($ch);
echo 'MKD nexus-app via PREQUOTE+NOCWD: errno='.curl_errno($ch).' '.curl_error($ch)."\n";
curl_close($ch);

// Upload file into nexus-app
$fh = fopen($tmp, 'rb');
$ch = curl_init("ftp://{$host}:{$port}/hello.txt");
curl_setopt_array($ch, [
    CURLOPT_USERPWD => $user.':'.$pass,
    CURLOPT_UPLOAD => true,
    CURLOPT_INFILE => $fh,
    CURLOPT_INFILESIZE => filesize($tmp),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 60,
    CURLOPT_FTP_USE_EPSV => true,
    CURLOPT_FTP_FILEMETHOD => CURLFTPMETHOD_NOCWD,
    CURLOPT_PREQUOTE => ['CWD ..', 'CWD nexus-app'],
]);
$ok = curl_exec($ch);
echo 'UPLOAD hello.txt: '.($ok === false ? 'ERR '.curl_errno($ch).' '.curl_error($ch) : 'OK')."\n";
curl_close($ch);
fclose($fh);

$ch = curl_init("ftp://{$host}:{$port}/");
curl_setopt_array($ch, [
    CURLOPT_USERPWD => $user.':'.$pass,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_DIRLISTONLY => true,
    CURLOPT_TIMEOUT => 45,
    CURLOPT_FTP_USE_EPSV => true,
    CURLOPT_FTP_FILEMETHOD => CURLFTPMETHOD_NOCWD,
    CURLOPT_PREQUOTE => ['CWD ..'],
]);
echo "\nPARENT:\n".trim((string) curl_exec($ch))."\n";
curl_close($ch);

$ch = curl_init("ftp://{$host}:{$port}/");
curl_setopt_array($ch, [
    CURLOPT_USERPWD => $user.':'.$pass,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_DIRLISTONLY => true,
    CURLOPT_TIMEOUT => 45,
    CURLOPT_FTP_USE_EPSV => true,
    CURLOPT_FTP_FILEMETHOD => CURLFTPMETHOD_NOCWD,
    CURLOPT_PREQUOTE => ['CWD ..', 'CWD nexus-app'],
]);
echo "\nPARENT/nexus-app:\n".trim((string) curl_exec($ch))."\n";
curl_close($ch);

$ch = curl_init("ftp://{$host}:{$port}/");
curl_setopt_array($ch, [
    CURLOPT_USERPWD => $user.':'.$pass,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_DIRLISTONLY => true,
    CURLOPT_TIMEOUT => 45,
    CURLOPT_FTP_USE_EPSV => true,
]);
echo "\nLOGIN:\n".trim((string) curl_exec($ch))."\n";
curl_close($ch);
@unlink($tmp);
