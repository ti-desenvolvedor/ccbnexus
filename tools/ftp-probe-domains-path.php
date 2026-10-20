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
    echo "=== {$label} ===\n";
    echo 'PREQUOTE: '.json_encode($prequote)."\n";
    echo ($body === false ? 'ERR '.curl_error($ch) : trim((string) $body))."\n\n";
    curl_close($ch);
};

$candidates = [
    'login' => [],
    'CWD ..' => ['CWD ..'],
    'CWD domains' => ['CWD domains'],
    'CWD .. + domains' => ['CWD ..', 'CWD domains'],
    'CWD .. + domains + ccb' => ['CWD ..', 'CWD domains', 'CWD ccb.tinexus.com.br'],
    'CWD domains + ccb from login' => ['CWD domains', 'CWD ccb.tinexus.com.br'],
];

foreach ($candidates as $label => $pre) {
    $list($label, $pre);
}
