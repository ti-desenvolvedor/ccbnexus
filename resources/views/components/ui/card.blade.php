@props([
    'title' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-800/80 dark:bg-slate-950']) }}>
    @if ($title || isset($toolbar))
        <div class="flex items-center justify-between gap-3 border-b border-slate-200/80 px-5 py-4 dark:border-slate-800/80">
            <div class="min-w-0">
                @if ($title)
                    <div class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $title }}</div>
                @endif
            </div>
            @isset($toolbar)
                <div class="shrink-0">{{ $toolbar }}</div>
            @endisset
        </div>
    @endif

    <div class="p-5">
        {{ $slot }}
    </div>
</div>
