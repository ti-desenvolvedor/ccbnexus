@props(['tone' => 'danger'])

@php
    $tones = [
        'danger' => ['icon' => 'danger', 'role' => 'alert', 'class' => 'text-rose-700 dark:text-rose-300'],
        'warning' => ['icon' => 'alert', 'role' => 'status', 'class' => 'text-amber-950 dark:text-amber-100'],
        'success' => ['icon' => 'check', 'role' => 'status', 'class' => 'text-emerald-800 dark:text-emerald-200'],
        'info' => ['icon' => 'info', 'role' => 'status', 'class' => 'text-slate-700 dark:text-slate-200'],
    ];
    $tone = array_key_exists($tone, $tones) ? $tone : 'danger';
    $meta = $tones[$tone];
@endphp

<p
    role="{{ $meta['role'] }}"
    {{ $attributes->merge(['class' => 'mt-1 flex items-start gap-1.5 text-sm '.$meta['class']]) }}
>
    <x-nexus.glyph :name="$meta['icon']" class="mt-0.5 h-4 w-4 shrink-0" />
    <span>{{ $slot }}</span>
</p>
