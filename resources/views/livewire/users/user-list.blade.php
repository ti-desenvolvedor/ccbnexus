<div class="space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Pesquisar por nome ou email…" class="max-w-md w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
        <x-nexus.searchable-select
            wire:model.live="strength"
            class="w-full sm:w-auto sm:min-w-[12rem]"
            placeholder="Todas as senhas"
            search-placeholder="Buscar..."
            :options="[
                ['value' => '', 'label' => 'Todas as senhas'],
                ['value' => 'review', 'label' => 'A trocar'],
                ['value' => 'strong', 'label' => 'Adequadas'],
            ]"
        />
    </div>
    <p class="text-xs text-slate-500">Fraca ou não verificada: peça ao utilizador para definir uma senha com letra, número e caractere especial. A senha em si não é mostrada.</p>
    <x-ui.card>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-600 dark:bg-slate-900 dark:text-slate-400">
                    <tr>
                        <th class="px-4 py-3">{{ __('Nome') }}</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Senha</th>
                        <th class="px-4 py-3">Âmbito</th>
                        <th class="px-4 py-3">Papéis</th>
                        <th class="px-4 py-3 text-right">{{ __('Ações') }}</th>
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
                            <td class="px-4 py-3">
                                <x-ui.badge :tone="$row->passwordStrengthTone()">{{ $row->passwordStrengthLabel() }}</x-ui.badge>
                                @if ($row->passwordNeedsChange())
                                    <p class="mt-1 text-xs text-slate-500">Peça a troca</p>
                                @endif
                            </td>
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
                                    <a href="{{ route('users.edit', $row) }}" wire:navigate class="text-primary-600 hover:underline">{{ __('Editar') }}</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Nenhum utilizador encontrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $rows->links() }}</div>
    </x-ui.card>
</div>
