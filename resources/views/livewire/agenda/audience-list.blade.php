<x-ui.card title="Públicos (audiências)">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase dark:bg-slate-900">
                <tr>
                    <th class="px-3 py-2">Nome</th>
                    <th class="px-3 py-2">Slug</th>
                    <th class="px-3 py-2 text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($audiences as $a)
                    <tr wire:key="au-{{ $a->id }}" class="border-t border-slate-100 dark:border-slate-800">
                        <td class="px-3 py-2 font-medium">{{ $a->name }}</td>
                        <td class="px-3 py-2 text-slate-600">{{ $a->slug }}</td>
                        <td class="px-3 py-2 text-right">
                            @can('update', $a)
                                <a href="{{ route('agenda.audiences.edit', $a) }}" wire:navigate class="text-primary-600 hover:underline">Editar</a>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $audiences->links() }}</div>
</x-ui.card>
