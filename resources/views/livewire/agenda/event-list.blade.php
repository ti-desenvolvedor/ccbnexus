<div class="space-y-4">
    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Pesquisar eventos…" class="max-w-md w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
    <x-ui.card>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase dark:bg-slate-900">
                    <tr>
                        <th class="px-4 py-3">Título</th>
                        <th class="px-4 py-3">Início</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($events as $e)
                        <tr wire:key="ev-{{ $e->id }}">
                            <td class="px-4 py-3 font-medium">{{ $e->title }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $e->starts_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3">{{ $e->status }}</td>
                            <td class="px-4 py-3 text-right space-x-3">
                                @can('respond', $e)
                                    <a href="{{ route('agenda.events.rsvp', $e) }}" wire:navigate class="text-primary-600 hover:underline">Confirmar</a>
                                @endcan
                                @can('update', $e)
                                    <a href="{{ route('agenda.events.edit', $e) }}" wire:navigate class="text-primary-600 hover:underline">Editar</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">Sem eventos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $events->links() }}</div>
    </x-ui.card>
</div>
