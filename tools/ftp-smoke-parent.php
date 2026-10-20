<?php

use App\Support\FtpCurlUploader;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$config = config('deploy.ftp');
echo 'remote_root config='.json_encode($config['remote_root']).PHP_EOL;

$uploader = new FtpCurlUploader($config);
echo 'remote_root used='.$uploader->remoteRoot().PHP_EOL;

$a = tempnam(sys_get_temp_dir(), 'a');
$b = tempnam(sys_get_temp_dir(), 'b');
file_put_contents($a, "laravel-side\n");
file_put_contents($b, "public-side\n");

$uploader->uploadFile($a, 'laravel/_smoke_laravel.txt');
$uploader->uploadFile($b, 'public_html/_smoke_public.txt');

$host = $config['host'];
$user = $config['username'];
$pass = $config['password'];
$port = (int) $config['port'];

$list = function (string $label, array $pre) use ($host, $user, $pass, $port): void {
    $ch = curl_init("ftp://{$host}:{$port}/");
    curl_setopt_array($ch, [
        CURLOPT_USERPWD => $user.':'.$pass,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_DIRLISTONLY => true,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_FTP_USE_EPSV => true,
        CURLOPT_FTP_FILEMETHOD => CURLFTPMETHOD_NOCWD,
        CURLOPT_PREQUOTE => $pre,
    ]);
    $body = curl_exec($ch);
    echo "=== {$label} ===\n".($body === false ? 'ERR '.curl_error($ch) : trim((string) $body))."\n\n";
    curl_close($ch);
};

$list('PASTA DA FOTO (pai)', ['CWD ..']);
$list('pai/laravel (deve ter _smoke_laravel.txt)', ['CWD ..', 'CWD laravel']);
$list('pai/public_html (deve ter _smoke_public.txt)', ['CWD ..', 'CWD public_html']);
$list('LOGIN=public_html direto (NAO deve ter _smoke_laravel.txt na raiz)', []);

@unlink($a);
@unlink($b);
