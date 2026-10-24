@props(['status'])

@if ($status)
    <x-nexus.alert tone="success" {{ $attributes }}>{{ $status }}</x-nexus.alert>
@endif
