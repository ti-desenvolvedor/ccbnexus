@props([
    'layout' => 'row',
    'showWordmark' => true,
])

<x-brand-logo :layout="$layout" :show-wordmark="$showWordmark" {{ $attributes }} />
