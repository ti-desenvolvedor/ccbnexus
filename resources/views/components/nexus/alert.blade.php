@props(['tone' => 'info'])

@php
    $tones = [
        'success' => [
            'icon' => 'check',
            'role' => 'status',
            'class' => 'border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-100',
        ],
        'warning' => [
            'icon' => 'alert',
            'role' => 'status',
            'class' => 'border-amber-200 bg-amber-50 text-amber-950 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-100',
        ],
        'danger' => [
            'icon' => 'danger',
            'role' => 'alert',
            'class' => 'border-rose-200 bg-rose-50 text-rose-950 dark:border-rose-900 dark:bg-rose-950 dark:text-rose-100',
        ],
        'info' => [
            'icon' => 'info',
            'role' => 'status',
            'class' => 'border-slate-200 bg-slate-50 text-slate-800 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100',
        ],
    ];
    $tone = array_key_exists($tone, $tones) ? $tone : 'info';
    $meta = $tones[$tone];
@endphp

<div
    role="{{ $meta['role'] }}"
    {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded-xl border px-4 py-3 text-sm leading-relaxed '.$meta['class']]) }}
>
    <x-nexus.glyph :name="$meta['icon']" class="mt-0.5 h-5 w-5 shrink-0" />
    <div class="min-w-0">{{ $slot }}</div>
</div>
