<?php

use App\Support\FtpCurlUploader;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$config = config('deploy.ftp');

echo 'HOST='.$config['host'].PHP_EOL;
echo 'USER='.$config['username'].PHP_EOL;
echo 'PORT='.$config['port'].PHP_EOL;
echo 'SSL='.(($config['ssl'] ?? false) ? '1' : '0').PHP_EOL;

try {
    (new FtpCurlUploader($config))->ping();
    echo "RESULT=SSL_OK\n";
    exit(0);
} catch (Throwable $e) {
    echo 'SSL_FAIL='.$e->getMessage().PHP_EOL;
}

$config['ssl'] = false;

try {
    (new FtpCurlUploader($config))->ping();
    echo "RESULT=PLAIN_OK\n";
    exit(0);
} catch (Throwable $e) {
    echo 'PLAIN_FAIL='.$e->getMessage().PHP_EOL;
    exit(1);
}
