<div
    x-data="nexusFormProgress"
    x-cloak
    x-show="total > 1"
    {{ $attributes->merge(['class' => 'w-full rounded-xl border border-slate-200 bg-white px-3 py-3 md:col-span-2 dark:border-slate-800 dark:bg-slate-950']) }}
    role="progressbar"
    aria-valuemin="0"
    :aria-valuenow="filled"
    :aria-valuemax="total"
    :aria-label="label"
>
    <div class="flex items-center justify-between gap-3">
        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100" x-text="label"></p>
        <p class="shrink-0 text-sm tabular-nums text-slate-700 dark:text-slate-300" x-text="filled + ' de ' + total"></p>
    </div>
    <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
        <div class="h-full rounded-full bg-primary-600" :style="`width: ${percent}%`"></div>
    </div>
</div>
