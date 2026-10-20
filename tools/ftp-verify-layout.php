<?php

use App\Support\FtpCurlUploader;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$uploader = new FtpCurlUploader(config('deploy.ftp'));

$tmpLaravel = tempnam(sys_get_temp_dir(), 'ftp');
$tmpPublic = tempnam(sys_get_temp_dir(), 'ftp');
file_put_contents($tmpLaravel, "laravel-root-ok\n");
file_put_contents($tmpPublic, "public-root-ok\n");

$uploader->uploadFile($tmpLaravel, 'laravel/_layout_check.txt');
$uploader->uploadFile($tmpPublic, 'public_html/_layout_check.txt');

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
    echo "=== {$label} ===\n".($body === false ? 'ERR '.curl_error($ch) : trim((string) $body))."\n\n";
    curl_close($ch);
};

$list('PARENT (deve ter laravel + public_html)', ['CWD ..']);
$list('PARENT/laravel (deve ter _layout_check.txt)', ['CWD ..', 'CWD laravel']);
$list('LOGIN/public_html (deve ter _layout_check.txt)', []);

@unlink($tmpLaravel);
@unlink($tmpPublic);
