<div class="space-y-4">
    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Pesquisar por nome ou email…" class="max-w-md w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
    <x-ui.card>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-600 dark:bg-slate-900 dark:text-slate-400">
                    <tr>
                        <th class="px-4 py-3">Nome</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Âmbito</th>
                        <th class="px-4 py-3">Papéis</th>
                        <th class="px-4 py-3 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($rows as $row)
                        <tr wire:key="user-{{ $row->id }}">
                            <td class="px-4 py-3 font-medium">
                                {{ $row->name }}
                                @if ($row->is_super_admin)
                                    <span class="ml-1 rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-amber-900 dark:bg-amber-900/40 dark:text-amber-100">Super</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $row->email }}</td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
                                @if ($row->prayerHouse)
                                    Casa: {{ $row->prayerHouse->name }}
                                @elseif ($row->administration)
                                    Adm.: {{ $row->administration->name }}
                                @elseif ($row->regional)
                                    Reg.: {{ $row->regional->name }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $row->roles->pluck('name')->join(', ') ?: '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                @can('update', $row)
                                    <a href="{{ route('users.edit', $row) }}" wire:navigate class="text-primary-600 hover:underline">Editar</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">Nenhum utilizador encontrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $rows->links() }}</div>
    </x-ui.card>
</div>
