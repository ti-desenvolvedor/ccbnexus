<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Deploy FTP (Hostinger / hospedagem compartilhada)
    |--------------------------------------------------------------------------
    |
    | Credenciais usadas por `php artisan ftp:push` para enviar o pacote
    | gerado por ftp:prepare / ftp:delta. Nao versionar senhas.
    |
    */

    'ftp' => [
        'host' => env('DEPLOY_FTP_HOST'),
        'username' => env('DEPLOY_FTP_USERNAME'),
        'password' => env('DEPLOY_FTP_PASSWORD'),
        'port' => (int) env('DEPLOY_FTP_PORT', 21),
        'ssl' => (bool) env('DEPLOY_FTP_SSL', true),
        'passive' => (bool) env('DEPLOY_FTP_PASSIVE', true),
        'timeout' => (int) env('DEPLOY_FTP_TIMEOUT', 120),
        // Pasta remota onde existem public_html/ e laravel/.
        // O FTP desta conta abre numa pasta filha da home: subir um nivel e entrar no dominio.
        // Se o FTP ja abre dentro de public_html, use "..".
        'remote_root' => env('DEPLOY_FTP_REMOTE_ROOT', '..'),
        // Envios paralelos (1–16). 8 costuma ir bem na Hostinger.
        'concurrency' => (int) env('DEPLOY_FTP_CONCURRENCY', 8),
    ],

    /*
    |--------------------------------------------------------------------------
    | Deploy SSH (Hostinger)
    |--------------------------------------------------------------------------
    |
    | Usado por `php artisan ssh:run` e por `ftp:push --remote`.
    | Se host/usuario/senha SSH estiverem vazios, reutiliza DEPLOY_FTP_*.
    | No hPanel: Avancado → SSH Access (ative SSH e use o host indicado).
    |
    */
    'ssh' => [
        'host' => env('DEPLOY_SSH_HOST'),
        'username' => env('DEPLOY_SSH_USERNAME'),
        'password' => env('DEPLOY_SSH_PASSWORD'),
        'port' => (int) env('DEPLOY_SSH_PORT', 22),
        'timeout' => (int) env('DEPLOY_SSH_TIMEOUT', 120),
    ],

    /*
    |--------------------------------------------------------------------------
    | Caminho SSH no servidor (Hostinger)
    |--------------------------------------------------------------------------
    |
    | Relativo ao home da conta. Ex.: domains/ccb.tinexus.com.br
    | Dentro dele ficam public_html/ e laravel/.
    |
    */
    'ssh_path' => env('DEPLOY_SSH_PATH', 'domains/ccb.tinexus.com.br'),
    'php_cli' => env('DEPLOY_PHP_CLI', '/opt/alt/php85/usr/bin/php'),

];
