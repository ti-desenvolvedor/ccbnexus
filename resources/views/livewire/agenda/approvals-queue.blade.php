<x-ui.card title="Aprovações pendentes">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase dark:bg-slate-900">
                <tr>
                    <th class="px-3 py-2">Evento</th>
                    <th class="px-3 py-2">Pedido por</th>
                    <th class="px-3 py-2 text-right">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($approvals as $a)
                    @php($ev = $a->approvable)
                    <tr wire:key="ap-{{ $a->id }}">
                        <td class="px-3 py-2">
                            @if ($ev instanceof \App\Models\Event)
                                {{ $ev->title }}
                            @else
                                #{{ $a->approvable_id }}
                            @endif
                        </td>
                        <td class="px-3 py-2">{{ $a->requestedBy?->name ?? '—' }}</td>
                        <td class="px-3 py-2 text-right space-x-2">
                            <button type="button" wire:click="approve({{ $a->id }})" class="text-emerald-600 hover:underline">Aprovar</button>
                            <button type="button" wire:click="reject({{ $a->id }})" class="text-rose-600 hover:underline">Rejeitar</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-3 py-6 text-center text-slate-500">Nada pendente.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $approvals->links() }}</div>
</x-ui.card>
