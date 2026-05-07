<x-ui.card title="Pedidos de acesso">
    <div class="mb-4">
        <label class="text-xs font-semibold text-slate-500">Nota de revisão (aprovar/rejeitar)</label>
        <textarea wire:model="review_note" rows="2" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950"></textarea>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase dark:bg-slate-900">
                <tr>
                    <th class="px-3 py-2">Nome</th>
                    <th class="px-3 py-2">Email</th>
                    <th class="px-3 py-2">Estado</th>
                    <th class="px-3 py-2 text-right">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @foreach ($requests as $r)
                    <tr wire:key="ar-{{ $r->id }}">
                        <td class="px-3 py-2">{{ $r->name }}</td>
                        <td class="px-3 py-2">{{ $r->email }}</td>
                        <td class="px-3 py-2">{{ $r->status }}</td>
                        <td class="px-3 py-2 text-right space-x-2">
                            @if ($r->status === 'pending')
                                <button type="button" wire:click="approve({{ $r->id }})" class="text-emerald-600 hover:underline">Aprovar</button>
                                <button type="button" wire:click="reject({{ $r->id }})" class="text-rose-600 hover:underline">Rejeitar</button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $requests->links() }}</div>
</x-ui.card>
