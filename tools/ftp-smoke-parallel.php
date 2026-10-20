<?php

use App\Support\FtpCurlUploader;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$uploader = (new FtpCurlUploader(config('deploy.ftp')))->withConcurrency(4);

$files = [];
for ($i = 1; $i <= 8; $i++) {
    $path = tempnam(sys_get_temp_dir(), 'p');
    file_put_contents($path, "parallel-{$i}\n");
    $files[] = [
        'local' => $path,
        'remote' => 'laravel/_parallel_smoke/file_'.$i.'.txt',
    ];
}

$started = microtime(true);
$failed = $uploader->uploadMany($files, 4);
$elapsed = round(microtime(true) - $started, 2);

echo 'concurrency=4 files=8 elapsed='.$elapsed."s\n";
echo $failed === [] ? "OK\n" : ("FAIL\n".implode("\n", $failed)."\n");

foreach ($files as $file) {
    @unlink($file['local']);
}
