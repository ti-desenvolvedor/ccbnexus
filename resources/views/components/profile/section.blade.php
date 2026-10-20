@props([
    'title',
    'description' => null,
])

<section {{ $attributes }}>
    <header class="flex gap-3">
        @isset($icon)
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-700 dark:bg-primary-950/60 dark:text-primary-300">
                {{ $icon }}
            </div>
        @endisset
        <div class="min-w-0 flex-1">
            <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ $title }}</h2>
            @if ($description)
                <p class="mt-0.5 text-sm text-slate-600 dark:text-slate-400">{{ $description }}</p>
            @endif
        </div>
    </header>
    <div class="mt-5">
        {{ $slot }}
    </div>
</section>
