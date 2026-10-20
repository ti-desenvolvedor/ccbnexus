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

$try = function (string $label, string $url, array $extra = []) use ($user, $pass): void {
    $ch = curl_init($url);
    $opts = [
        CURLOPT_USERPWD => $user.':'.$pass,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_DIRLISTONLY => true,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_FTP_USE_EPSV => true,
    ] + $extra;
    curl_setopt_array($ch, $opts);
    $body = curl_exec($ch);
    $err = curl_error($ch);
    $errno = curl_errno($ch);
    curl_close($ch);
    echo "=== {$label} ===\n";
    echo "URL={$url}\n";
    if ($body === false) {
        echo "ERR={$errno} {$err}\n\n";

        return;
    }
    echo trim((string) $body)."\n\n";
};

// CWD .. via quote e listar
$ch = curl_init("ftp://{$host}:{$port}/");
curl_setopt_array($ch, [
    CURLOPT_USERPWD => $user.':'.$pass,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_DIRLISTONLY => true,
    CURLOPT_TIMEOUT => 45,
    CURLOPT_FTP_USE_EPSV => true,
    CURLOPT_QUOTE => ['CWD ..', 'PWD'],
]);
$body = curl_exec($ch);
$err = curl_error($ch);
$errno = curl_errno($ch);
curl_close($ch);
echo "=== QUOTE CWD .. + PWD ===\n";
echo ($body === false ? "ERR={$errno} {$err}" : trim((string) $body))."\n\n";

$candidates = [
    'encoded parent' => "ftp://{$host}:{$port}/%2e%2e/",
    'domains path' => "ftp://{$host}:{$port}/domains/",
    'home-ish' => "ftp://{$host}:{$port}/../",
];

// Tentativas comuns Hostinger a partir do usuario
$userBase = explode('.', $user)[0] ?? '';
if ($userBase !== '') {
    $candidates["abs home {$userBase}"] = "ftp://{$host}:{$port}/home/{$userBase}/";
    $candidates['abs domains ccb'] = "ftp://{$host}:{$port}/home/{$userBase}/domains/ccb.tinexus.com.br/";
    $candidates['abs public_html'] = "ftp://{$host}:{$port}/home/{$userBase}/domains/ccb.tinexus.com.br/public_html/";
}

foreach ($candidates as $label => $url) {
    $try($label, $url);
}
