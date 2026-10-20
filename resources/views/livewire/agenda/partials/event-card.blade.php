<article class="rounded-xl border border-slate-200 p-3 text-sm dark:border-slate-800">
    <div class="font-semibold">{{ $e->title }}</div>
    <p class="mt-1 text-slate-600 dark:text-slate-300">{{ $e->starts_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }} · {{ $e->eventType?->name ?? '—' }}</p>
    <p class="text-slate-600 dark:text-slate-300">{{ $e->meetingRoom?->name ?? $e->location?->name ?? '—' }} · {{ $e->attendantLabel() ?: '—' }}</p>
    <div class="mt-2 flex flex-wrap items-center gap-2">
        @if ($e->include_on_baptism_list)
            <x-ui.badge tone="info">Boletim</x-ui.badge>
        @endif
        @if ($e->whatsapp_enabled)
            <x-ui.badge tone="success">WhatsApp</x-ui.badge>
        @endif
        <x-ui.badge :tone="$e->effectiveStatusTone()">{{ $e->effectiveStatusLabel() }}</x-ui.badge>
    </div>
    <div class="mt-2 flex flex-wrap gap-3">
        @can('respond', $e)
            <a href="{{ route('agenda.events.rsvp', $e) }}" wire:navigate class="inline-flex min-h-[44px] items-center text-primary-700 hover:underline dark:text-primary-300">Confirmar</a>
        @endcan
        @can('update', $e)
            <a href="{{ route('agenda.events.edit', $e->agendaEditId()) }}" wire:navigate class="inline-flex min-h-[44px] items-center text-primary-700 hover:underline dark:text-primary-300">Editar</a>
        @endcan
    </div>
</article>
