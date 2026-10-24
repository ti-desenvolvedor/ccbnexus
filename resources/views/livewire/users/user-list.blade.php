<div class="space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Pesquisar por nome ou email…" class="min-h-11 w-full max-w-md rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
        <x-nexus.searchable-select
            wire:model.live="strength"
            class="w-full sm:w-auto sm:min-w-[12rem]"
            placeholder="Todas as senhas"
            search-placeholder="Buscar..."
            :options="[
                ['value' => '', 'label' => 'Todas as senhas'],
                ['value' => 'review', 'label' => 'A trocar'],
                ['value' => 'medium', 'label' => 'Médias'],
                ['value' => 'strong', 'label' => 'Fortes'],
            ]"
        />
    </div>
    <p class="text-xs text-slate-500">Fraca ou não verificada: peça a troca. O mínimo inclui maiúscula e minúscula. Forte tem 12 caracteres ou mais. A senha não é mostrada.</p>
    @if ($statusMessage)
        <x-nexus.alert tone="success">{{ $statusMessage }}</x-nexus.alert>
    @endif
    @error('reset')
        <x-nexus.alert tone="danger">{{ $message }}</x-nexus.alert>
    @enderror
    @if ($impersonateUserId)
        <form wire:submit="startImpersonation" class="space-y-3 rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/40">
            <div>
                <h2 class="flex items-center gap-2 text-sm font-semibold text-amber-950 dark:text-amber-100">
                    <x-nexus.glyph name="eye" class="h-4 w-4 shrink-0" />
                    Personificar {{ $impersonateName }}
                </h2>
                <p class="mt-1 text-sm text-amber-950 dark:text-amber-100">O painel passa a usar as permissões desta pessoa. A senha dela não é pedida nem mostrada.</p>
            </div>
            <div>
                <label for="impersonate-reason" class="text-sm font-semibold text-amber-950 dark:text-amber-100">Motivo do suporte</label>
                <textarea id="impersonate-reason" wire:model="justification" rows="2" required class="mt-1 block min-h-11 w-full rounded-xl border border-amber-200 bg-white px-3 py-2 text-sm text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-amber-900 dark:bg-slate-950 dark:text-slate-100" placeholder="Ex.: conferir a fila de reservas desta casa"></textarea>
                @error('justification')
                    <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message>
                @enderror
            </div>
            <div class="flex flex-col gap-2 sm:flex-row">
                <button type="submit" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-amber-950 px-4 text-sm font-semibold text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-950">
                    <svg class="h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                    </svg>
                    Entrar como esta pessoa
                </button>
                <button type="button" wire:click="cancelImpersonation" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-amber-300 px-4 text-sm font-semibold text-amber-950 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-amber-800 dark:text-amber-100">Cancelar</button>
            </div>
        </form>
    @endif
    <x-ui.card class="overflow-hidden">
        <div class="-m-5">
            <div class="space-y-3 p-5 md:hidden">
                @forelse ($rows as $row)
                    @php
                        $active = (int) $impersonateUserId === (int) $row->id;
                        $stripe = $active
                            ? 'border-amber-300 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/40'
                            : ($loop->even
                                ? 'border-slate-200 bg-slate-100 dark:border-slate-800 dark:bg-slate-900'
                                : 'border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950');
                    @endphp
                    <article wire:key="user-card-{{ $row->id }}" class="space-y-3 rounded-xl border p-4 {{ $stripe }}">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="truncate text-base font-semibold text-slate-900 dark:text-slate-100">{{ $row->name }}</h2>
                                @if ($row->is_super_admin)
                                    <span class="rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-amber-900 dark:bg-amber-900/40 dark:text-amber-100">Super</span>
                                @endif
                            </div>
                            <p class="truncate text-sm text-slate-700 dark:text-slate-300">{{ $row->email }}</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <x-ui.badge :tone="$row->passwordStrengthTone()">{{ $row->passwordStrengthLabel() }}</x-ui.badge>
                            @if ($row->passwordNeedsChange())
                                <span class="text-xs font-medium text-slate-600 dark:text-slate-300">Peça a troca</span>
                            @endif
                        </div>
                        <p class="text-sm text-slate-800 dark:text-slate-200">
                            @if ($row->prayerHouse)
                                Casa: {{ $row->prayerHouse->name }}
                            @elseif ($row->administration)
                                Adm.: {{ $row->administration->name }}
                            @elseif ($row->regional)
                                Reg.: {{ $row->regional->name }}
                            @else
                                Sem âmbito
                            @endif
                            <span class="text-slate-500 dark:text-slate-400">· {{ $row->roles->pluck('name')->join(', ') ?: 'Sem papel' }}</span>
                        </p>
                        @include('livewire.users.partials.row-actions', ['row' => $row, 'align' => 'start'])
                    </article>
                @empty
                    <x-nexus.empty-state title="Nenhum utilizador encontrado" description="Ajuste a pesquisa ou o filtro de senha." />
                @endforelse
            </div>
            <div class="hidden overflow-x-auto md:block">
                <table class="min-w-full border-separate border-spacing-0 text-sm">
                    <thead>
                        <tr class="bg-slate-100 text-left text-slate-800 dark:bg-slate-900 dark:text-slate-100">
                            <th scope="col" class="px-4 py-3 font-semibold">{{ __('Nome') }}</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Email</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Senha</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Âmbito</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Papéis</th>
                            <th scope="col" class="px-4 py-3 text-right font-semibold">{{ __('Ações') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            @php
                                $active = (int) $impersonateUserId === (int) $row->id;
                                $stripe = $active
                                    ? 'bg-amber-50 dark:bg-amber-950/40'
                                    : ($loop->even ? 'bg-slate-100 dark:bg-slate-900' : 'bg-white dark:bg-slate-950');
                            @endphp
                            <tr wire:key="user-{{ $row->id }}" class="{{ $stripe }}">
                                <td class="whitespace-nowrap px-4 py-3 align-middle font-semibold text-slate-900 dark:text-slate-100">
                                    <span class="inline-flex items-center gap-2">
                                        {{ $row->name }}
                                        @if ($row->is_super_admin)
                                            <span class="rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-amber-900 dark:bg-amber-900/40 dark:text-amber-100">Super</span>
                                        @endif
                                    </span>
                                </td>
                                <td class="px-4 py-3 align-middle text-slate-700 dark:text-slate-300">
                                    <span class="block max-w-[16rem] truncate" title="{{ $row->email }}">{{ $row->email }}</span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 align-middle">
                                    <span class="inline-flex items-center gap-2">
                                        <x-ui.badge :tone="$row->passwordStrengthTone()">{{ $row->passwordStrengthLabel() }}</x-ui.badge>
                                        @if ($row->passwordNeedsChange())
                                            <span class="text-xs font-medium text-slate-600 dark:text-slate-300">Peça a troca</span>
                                        @endif
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 align-middle text-slate-800 dark:text-slate-200">
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
                                <td class="px-4 py-3 align-middle text-slate-700 dark:text-slate-300">{{ $row->roles->pluck('name')->join(', ') ?: '—' }}</td>
                                <td class="px-4 py-3 align-middle">
                                    @include('livewire.users.partials.row-actions', ['row' => $row, 'align' => 'end'])
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="bg-white px-4 py-2 dark:bg-slate-950">
                                    <x-nexus.empty-state title="Nenhum utilizador encontrado" description="Ajuste a pesquisa ou o filtro de senha." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800">{{ $rows->links() }}</div>
        </div>
    </x-ui.card>
</div>
