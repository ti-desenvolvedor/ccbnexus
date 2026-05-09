@props([
    'layout' => 'row',
    'showWordmark' => true,
])

@php
    $column = $layout === 'column';
@endphp

<div
    {{ $attributes->merge([
        'class' => $column
            ? 'flex min-w-0 flex-col items-center gap-3 text-center'
            : 'flex min-w-0 items-center gap-3',
    ]) }}
    aria-label="{{ config('app.name') }}"
>
    {{-- Quadradinho como antes: primary + duas linhas (CCB / NEXUS) --}}
    <div
        class="flex h-15 w-15 shrink-0 flex-col items-center justify-center rounded-xl bg-primary-600 px-2 py-2 text-center text-white shadow-sm ring-1 ring-primary-500/40 dark:ring-primary-400/35"
        aria-hidden="true"
    >
        <span class="py-2 px-1 text-[13px] font-bold leading-none tracking-tight">CCB</span>
        <span class="mt-1 text-[8px] font-semibold uppercase tracking-[0.12em] text-white/90"></span>
    </div>

    @if ($showWordmark)
        <div class="min-w-0 flex-1 {{ $column ? 'flex flex-col items-center' : '' }}">
            <span class="block truncate text-base font-bold leading-tight tracking-tight text-primary-700 dark:text-primary-400">NEXUS</span>
            @isset($subtitle)
                <div class="mt-0.5 min-w-0 truncate text-xs font-medium text-[rgb(var(--sidebar-muted)_/_1)]">{{ $subtitle }}</div>
            @endisset
        </div>
    @endif
</div>
