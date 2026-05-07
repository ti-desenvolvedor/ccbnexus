<div class="space-y-4">
    <div class="flex flex-wrap gap-3">
        <input
            type="search"
            wire:model.live.debounce.300ms="search"
            placeholder="Pesquisar…"
            class="max-w-md flex-1 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950"
        />
    </div>

    <x-ui.card>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-600 dark:bg-slate-900 dark:text-slate-400">
                    <tr>
                        <th class="px-4 py-3">Nome</th>
                        <th class="px-4 py-3">Slug</th>
                        <th class="px-4 py-3">Local</th>
                        <th class="px-4 py-3">Ativa</th>
                        <th class="px-4 py-3 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($regionals as $regional)
                        <tr wire:key="regional-{{ $regional->id }}">
                            <td class="px-4 py-3 font-medium text-slate-900 dark:text-slate-100">{{ $regional->name }}</td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $regional->slug }}</td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $regional->location?->name ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $regional->is_active ? 'Sim' : 'Não' }}</td>
                            <td class="px-4 py-3 text-right">
                                @can('update', $regional)
                                    <a href="{{ route('organization.regionals.edit', $regional) }}" wire:navigate class="text-primary-600 hover:underline">Editar</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-slate-500">Nenhuma regional encontrada.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4 px-2">{{ $regionals->links() }}</div>
    </x-ui.card>
</div>
