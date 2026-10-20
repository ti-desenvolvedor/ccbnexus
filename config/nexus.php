<?php

return [
    /*
    | Senha inicial do admin (DevAdminUserSeeder).
    | Com config:cache, NÃO usar env() no seeder — só via config.
    | Defina RESET_ADMIN_PASSWORD=true no .env para reaplicar em re-seed.
    */
    'initial_admin_password' => env('INITIAL_ADMIN_PASSWORD'),
    'reset_admin_password' => (bool) env('RESET_ADMIN_PASSWORD', false),

    /*
    | Self-registration público. Em produção deve permanecer false —
    | contas entram via pedido de acesso + aprovação.
    */
    'allow_public_registration' => (bool) env('NEXUS_ALLOW_PUBLIC_REGISTRATION', false),

    /*
    | Senha nova: letra + número + símbolo, mínimo de caracteres.
    | Login: a partir de captcha_after falhas pede o código da imagem;
    | ao chegar em max_attempts bloqueia por lock_seconds.
    */
    'auth' => [
        'password_min' => 8,
        'max_attempts' => 5,
        'captcha_after' => 2,
        'lock_seconds' => 900,
    ],

    'profile_photo' => [
        'max_kb' => 2048,
        'disk' => 'public',
    ],

    /*
    | Calendário da agenda. brasilapi busca os nacionais; se a API falhar, usa a lista local.
    | Nos testes o driver fica em catalog para não depender da rede.
    */
    'holiday_sync' => [
        'driver' => env('HOLIDAY_SYNC_DRIVER', 'brasilapi'),
    ],

    'pagination' => [
        'default' => 20,
        'per_page_options' => [10, 15, 20, 25, 50, 100],
        'per_page_min' => 5,
        'per_page_max' => 200,
    ],

    'ui' => [
        'themes' => ['light', 'dark'],
        'palettes' => [
            'blue',
            'navy',
            'green',
            'green_dark',
            'red',
            'red_dark',
            'orange',
            'brown',
        ],
        'default_theme' => 'light',
        'default_palette' => 'blue',
    ],
];
