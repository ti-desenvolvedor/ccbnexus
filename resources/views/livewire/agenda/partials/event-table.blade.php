<x-ui.card>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
            <caption class="sr-only">Eventos da agenda</caption>
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase dark:bg-slate-900">
                <tr>
                    <th class="px-4 py-3">Data</th>
                    <th class="px-4 py-3">Título</th>
                    <th class="px-4 py-3">Tipo</th>
                    <th class="px-4 py-3">Local</th>
                    <th class="px-4 py-3">Quem atende</th>
                    <th class="px-4 py-3">Canais</th>
                    <th class="px-4 py-3">Situação</th>
                    <th class="px-4 py-3 text-right">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @foreach ($events as $e)
                    <tr wire:key="ev-{{ $e->id }}">
                        <td class="px-4 py-3 text-slate-600">{{ $e->starts_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 font-medium">{{ $e->title }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $e->eventType?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $e->meetingRoom?->name ?? $e->location?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $e->attendantLabel() ?: '—' }}</td>
                        <td class="px-4 py-3">
                            @if ($e->include_on_baptism_list)
                                <x-ui.badge tone="info">Boletim</x-ui.badge>
                            @endif
                            @if ($e->whatsapp_enabled)
                                <x-ui.badge tone="success">WhatsApp</x-ui.badge>
                            @endif
                            @if (! $e->include_on_baptism_list && ! $e->whatsapp_enabled)
                                <span class="text-slate-500">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <x-ui.badge :tone="$e->effectiveStatusTone()">{{ $e->effectiveStatusLabel() }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex flex-wrap justify-end gap-3">
                                @can('respond', $e)
                                    <a href="{{ route('agenda.events.rsvp', $e) }}" wire:navigate class="inline-flex min-h-[44px] items-center text-primary-700 hover:underline dark:text-primary-300">Confirmar</a>
                                @endcan
                                @can('update', $e)
                                    <a href="{{ route('agenda.events.edit', $e->agendaEditId()) }}" wire:navigate class="inline-flex min-h-[44px] items-center text-primary-700 hover:underline dark:text-primary-300">Editar</a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-ui.card>
