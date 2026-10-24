@props(['size' => 'lg'])

@php
    $box = $size === 'lg' ? 'h-8 w-8' : 'h-5 w-5';
    $circle = $size === 'lg' ? 'h-16 w-16' : 'h-10 w-10';
    $icons = ['check', 'alert', 'danger', 'info', 'mail', 'trash', 'ban', 'reject', 'refresh', 'help', 'photo'];
@endphp

<span
    {{ $attributes->merge(['class' => 'inline-flex '.$circle.' shrink-0 items-center justify-center rounded-full']) }}
    :class="{
        'bg-rose-50 text-rose-800 dark:bg-rose-950/40 dark:text-rose-200': $store.nexusDialog.tone === 'danger',
        'bg-amber-100 text-amber-950 dark:bg-amber-950 dark:text-amber-100': $store.nexusDialog.tone === 'warning',
        'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-100': $store.nexusDialog.tone === 'success',
        'bg-primary-50 text-primary-700 dark:bg-slate-800 dark:text-primary-100': !['danger', 'warning', 'success'].includes($store.nexusDialog.tone),
    }"
>
    @foreach ($icons as $icon)
        <span x-show="$store.nexusDialog.icon === '{{ $icon }}'" x-cloak class="inline-flex">
            <x-nexus.glyph :name="$icon" @class([$box]) />
        </span>
    @endforeach
</span>
