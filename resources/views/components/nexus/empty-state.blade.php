@props([
    'title' => 'Nenhum registo',
    'description' => null,
    'href' => null,
    'actionLabel' => 'Criar',
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center gap-3 px-4 py-12 text-center']) }}>
    <div class="grid h-12 w-12 place-items-center rounded-2xl bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300">
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
            <path stroke-width="2" stroke-linecap="round" d="M4 7h16M4 12h10M4 17h16" />
        </svg>
    </div>
    <div>
        <div class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $title }}</div>
        @if ($description)
            <p class="mt-1 max-w-sm text-xs text-slate-500 dark:text-slate-400">{{ $description }}</p>
        @endif
    </div>
    @if ($href)
        <a href="{{ $href }}" wire:navigate class="inline-flex min-h-[44px] items-center rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-700">
            {{ $actionLabel }}
        </a>
    @endif
    {{ $slot }}
</div>
