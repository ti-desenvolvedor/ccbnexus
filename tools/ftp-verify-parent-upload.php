<?php

use App\Support\FtpCurlUploader;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$uploader = new FtpCurlUploader(config('deploy.ftp'));
$tmp = tempnam(sys_get_temp_dir(), 'ftp');
file_put_contents($tmp, "ok-parent\n");

$uploader->uploadFile($tmp, 'laravel/_ftp_parent_check.txt');
echo 'uploaded laravel/_ftp_parent_check.txt via uploader (remote_root='.$uploader->remoteRoot().")\n";

$cfg = config('deploy.ftp');
$host = $cfg['host'];
$user = $cfg['username'];
$pass = $cfg['password'];
$port = (int) $cfg['port'];

$ch = curl_init("ftp://{$host}:{$port}/");
curl_setopt_array($ch, [
    CURLOPT_USERPWD => $user.':'.$pass,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_DIRLISTONLY => true,
    CURLOPT_TIMEOUT => 45,
    CURLOPT_FTP_USE_EPSV => true,
    CURLOPT_PREQUOTE => ['CWD ..'],
]);
echo "PARENT:\n".trim((string) curl_exec($ch))."\n\n";
curl_close($ch);

$ch = curl_init("ftp://{$host}:{$port}/");
curl_setopt_array($ch, [
    CURLOPT_USERPWD => $user.':'.$pass,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_DIRLISTONLY => true,
    CURLOPT_TIMEOUT => 45,
    CURLOPT_FTP_USE_EPSV => true,
]);
echo "LOGIN DIR (public_html):\n".trim((string) curl_exec($ch))."\n";
curl_close($ch);
@unlink($tmp);
