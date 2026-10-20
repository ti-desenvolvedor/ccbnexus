<?php

$root = dirname(__DIR__);
$traitFqn = 'App\\Livewire\\Concerns\\UsesAccountPagination';
$traitShort = 'UsesAccountPagination';

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root.'/app/Livewire', FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    $content = file_get_contents($path);

    if (! preg_match('/->paginate\(\d+\)/', $content)) {
        continue;
    }

    $original = $content;

    if (! str_contains($content, $traitShort)) {
        if (! str_contains($content, 'use '.$traitFqn.';')) {
            $content = preg_replace(
                '/(namespace [^;]+;\n)/',
                "$1\nuse {$traitFqn};\n",
                $content,
                1
            );
        }

        if (preg_match('/class \w+[^{]*\{(\n\s+use [^;]+;)+/', $content)) {
            $content = preg_replace(
                '/(class \w+[^{]*\{)(\n\s+use [^;]+;)+/',
                "$1$2\n    use {$traitShort};",
                $content,
                1
            );
        } else {
            $content = preg_replace(
                '/(class \w+[^{]*\{)/',
                "$1\n    use {$traitShort};",
                $content,
                1
            );
        }
    }

    $content = preg_replace('/->paginate\(\d+\)/', '->paginate($this->paginatePerPage())', $content);

    if ($content !== $original) {
        file_put_contents($path, $content);
        echo str_replace($root.'/', '', $path).PHP_EOL;
    }
}
