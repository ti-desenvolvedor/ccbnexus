<x-ui.card title="Estacionamentos">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase dark:bg-slate-900">
                <tr>
                    <th class="px-3 py-2">Nome</th>
                    <th class="px-3 py-2">Local</th>
                    <th class="px-3 py-2">Cap.</th>
                    <th class="px-3 py-2 text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($parkings as $p)
                    <tr wire:key="pk-{{ $p->id }}" class="border-t border-slate-100 dark:border-slate-800">
                        <td class="px-3 py-2 font-medium">{{ $p->name }}</td>
                        <td class="px-3 py-2 text-slate-600">{{ $p->location?->name }}</td>
                        <td class="px-3 py-2">{{ $p->capacity ?? '—' }}</td>
                        <td class="px-3 py-2 text-right">
                            @can('update', $p)
                                <a href="{{ route('infrastructure.parkings.edit', $p) }}" wire:navigate class="text-primary-600 hover:underline">Editar</a>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $parkings->links() }}</div>
</x-ui.card>
