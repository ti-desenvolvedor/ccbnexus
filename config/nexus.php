<?php

return [
    /*
    | Senha inicial do admin (DevAdminUserSeeder).
    | Com config:cache, NÃO usar env() no seeder — só via config.
    | Defina RESET_ADMIN_PASSWORD=true no .env para reaplicar em re-seed.
    */
    'initial_admin_password' => env('INITIAL_ADMIN_PASSWORD'),
    'reset_admin_password' => (bool) env('RESET_ADMIN_PASSWORD', false),

    'profile_photo' => [
        'max_kb' => 2048,
        'disk' => 'public',
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
