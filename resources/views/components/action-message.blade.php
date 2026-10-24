@props(['on'])

<div
    x-data="{ shown: false, timeout: null }"
    x-init="@this.on('{{ $on }}', () => { clearTimeout(timeout); shown = true; timeout = setTimeout(() => { shown = false }, 2000); })"
    x-show.transition.out.opacity.duration.1500ms="shown"
    x-transition:leave.opacity.duration.1500ms
    style="display: none;"
    role="status"
    {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 text-sm font-medium text-primary-700 dark:text-primary-300']) }}
>
    <x-nexus.glyph name="check" class="h-4 w-4 shrink-0" />
    <span>{{ $slot->isEmpty() ? __('Guardado.') : $slot }}</span>
</div>
