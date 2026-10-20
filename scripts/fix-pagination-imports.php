<?php

$files = array_merge(glob(__DIR__.'/../app/Livewire/*/*.php') ?: []);

foreach ($files as $file) {
    $content = file_get_contents($file);
    if (! str_contains($content, 'use UsesAccountPagination')) {
        continue;
    }
    if (str_contains($content, 'use App\Livewire\Concerns\UsesAccountPagination')) {
        continue;
    }
    $content = preg_replace(
        '/(namespace [^;]+;\R)/',
        "$1\nuse App\Livewire\Concerns\UsesAccountPagination;",
        $content,
        1,
        $count
    );
    if ($count) {
        file_put_contents($file, $content);
        echo "Fixed: {$file}\n";
    }
}
