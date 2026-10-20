@props([
    'user' => null,
    'size' => 'md',
    'class' => '',
])

@php
    $user = $user ?? auth()->user();
    $sizeClasses = match ($size) {
        'xs' => 'h-8 w-8 text-xs',
        'sm' => 'h-10 w-10 text-sm',
        'md' => 'h-12 w-12 text-sm',
        'lg' => 'h-20 w-20 text-2xl',
        'xl' => 'h-28 w-28 text-3xl',
        '2xl' => 'h-36 w-36 text-4xl',
        default => 'h-12 w-12 text-sm',
    };
    $photoUrl = $user?->profilePhotoUrl();
    $initials = $user?->profileInitials() ?? '?';
@endphp

<div
    {{ $attributes->merge([
        'class' => "relative inline-flex shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-primary-600 font-bold text-white shadow-md ring-2 ring-white/80 dark:ring-slate-900/80 {$sizeClasses} {$class}",
    ]) }}
>
    @if ($photoUrl)
        <img
            src="{{ $photoUrl }}"
            alt="{{ $user?->name }}"
            class="h-full w-full object-cover"
            loading="lazy"
        />
    @else
        <span aria-hidden="true">{{ $initials }}</span>
    @endif
</div>
