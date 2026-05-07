<x-ui.card title="Reservas">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-600 dark:bg-slate-900 dark:text-slate-400">
                <tr>
                    <th class="px-4 py-3">Título</th>
                    <th class="px-4 py-3">Sala</th>
                    <th class="px-4 py-3">Início</th>
                    <th class="px-4 py-3">Fim</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3 text-right">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($reservations as $row)
                    <tr wire:key="rr-{{ $row->id }}">
                        <td class="px-4 py-3 font-medium">{{ $row->title }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $row->meetingRoom?->name }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $row->starts_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $row->ends_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">{{ $row->status }}</td>
                        <td class="px-4 py-3 text-right space-x-2">
                            @can('approve', $row)
                                @if ($row->status === 'pending')
                                    <button type="button" wire:click="approve({{ $row->id }})" class="text-sm text-emerald-600 hover:underline">Aprovar</button>
                                    <button type="button" wire:click="reject({{ $row->id }})" class="text-sm text-rose-600 hover:underline">Rejeitar</button>
                                @endif
                            @endcan
                            @can('delete', $row)
                                @if (! in_array($row->status, ['cancelled'], true))
                                    <button type="button" wire:click="cancel({{ $row->id }})" class="text-sm text-slate-600 hover:underline">Cancelar</button>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Nenhuma reserva.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $reservations->links() }}</div>
</x-ui.card>
