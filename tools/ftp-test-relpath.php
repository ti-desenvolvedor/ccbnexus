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
file_put_contents($tmp, "relpath-ok\n");

$variants = [
    'abs /laravel/x' => "ftp://{$host}:{$port}/laravel/_rel_abs.txt",
    'rel ./laravel/x' => "ftp://{$host}:{$port}/./laravel/_rel_dot.txt",
    'no-leading style' => "ftp://{$host}:{$port}/laravel/_rel_plain.txt",
];

foreach ($variants as $label => $url) {
    $fh = fopen($tmp, 'rb');
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_USERPWD => $user.':'.$pass,
        CURLOPT_UPLOAD => true,
        CURLOPT_INFILE => $fh,
        CURLOPT_INFILESIZE => filesize($tmp),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_FTP_USE_EPSV => true,
        CURLOPT_FTP_CREATE_MISSING_DIRS => CURLFTP_CREATE_DIR_RETRY,
        CURLOPT_PREQUOTE => ['CWD ..'],
    ]);
    $ok = curl_exec($ch);
    echo $label.' => '.($ok === false ? 'ERR '.curl_errno($ch).' '.curl_error($ch) : 'OK')." URL={$url}\n";
    curl_close($ch);
    fclose($fh);
}

foreach (['PARENT' => true, 'LOGIN' => false] as $label => $parent) {
    $ch = curl_init("ftp://{$host}:{$port}/");
    $opts = [
        CURLOPT_USERPWD => $user.':'.$pass,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_DIRLISTONLY => true,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_FTP_USE_EPSV => true,
    ];
    if ($parent) {
        $opts[CURLOPT_PREQUOTE] = ['CWD ..'];
    }
    curl_setopt_array($ch, $opts);
    echo "\n{$label}:\n".trim((string) curl_exec($ch))."\n";
    curl_close($ch);
}

// list parent/laravel if any
$ch = curl_init("ftp://{$host}:{$port}/laravel/");
curl_setopt_array($ch, [
    CURLOPT_USERPWD => $user.':'.$pass,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_DIRLISTONLY => true,
    CURLOPT_TIMEOUT => 45,
    CURLOPT_FTP_USE_EPSV => true,
    CURLOPT_PREQUOTE => ['CWD ..'],
]);
$body = curl_exec($ch);
echo "\nPARENT/laravel:\n".($body === false ? 'ERR '.curl_error($ch) : trim((string) $body))."\n";
curl_close($ch);
@unlink($tmp);
