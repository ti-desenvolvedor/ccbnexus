@props([
    'variant' => 'primary', // primary|secondary|ghost|danger
    'size' => 'md', // sm|md
])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-xl font-semibold transition focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-slate-950 disabled:opacity-50';

    $sizes = [
        'sm' => 'px-3 py-2 text-xs',
        'md' => 'px-4 py-2.5 text-sm',
    ];

    $variants = [
        'primary' => 'bg-primary-600 text-white hover:bg-primary-700',
        'secondary' => 'border border-slate-200 bg-white text-slate-900 hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100 dark:hover:bg-slate-900',
        'ghost' => 'text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-900',
        'danger' => 'bg-rose-600 text-white hover:bg-rose-700',
    ];

    $class = $base.' '.$sizes[$size].' '.$variants[$variant];
    $asLink = $attributes->has('href');
@endphp

@if ($asLink)
    <a {{ $attributes->merge(['class' => $class]) }}>
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['type' => 'button', 'class' => $class]) }}>
        {{ $slot }}
    </button>
@endif
