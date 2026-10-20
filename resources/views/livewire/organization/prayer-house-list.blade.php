<div class="space-y-4">
    <div class="flex flex-wrap items-end gap-3">
        <div class="min-w-[12rem] flex-1">
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('Pesquisar') }}</label>
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Código, nome ou slug…"
                class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950"
            />
        </div>
        <div class="min-w-[10rem]">
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('Regional') }}</label>
            <x-nexus.searchable-select wire:model.live="regionalId" class="mt-1" empty-label="Todas" search-placeholder="{{ __('Buscar regional...') }}" :options="$regionals" />
        </div>
        <div class="min-w-[10rem]">
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('Administração') }}</label>
            <x-nexus.searchable-select
                wire:model.live="administrationId"
                class="mt-1"
                empty-label="Todas"
                search-placeholder="{{ __('Buscar administração...') }}"
                :options="$administrations->map(fn ($administration) => ['value' => (string) $administration->id, 'label' => $administration->name.($regionalId === '' && $administration->regional ? ' — '.$administration->regional->name : '')])"
            />
        </div>
        <div class="min-w-[8rem]">
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('Estado') }}</label>
            <x-nexus.searchable-select
                wire:model.live="status"
                class="mt-1"
                empty-label="Todas"
                search-placeholder="Buscar estado..."
                :options="[
                    ['value' => 'active', 'label' => 'Ativas'],
                    ['value' => 'inactive', 'label' => 'Inativas'],
                ]"
            />
        </div>
        @if ($search !== '' || $regionalId !== '' || $administrationId !== '' || $status !== '')
            <button
                type="button"
                wire:click="clearFilters"
                class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-800 dark:text-slate-400 dark:hover:bg-slate-900"
            >
                Limpar filtros
            </button>
        @endif
    </div>

    <x-ui.card>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-600 dark:bg-slate-900 dark:text-slate-400">
                    <tr>
                        <th class="px-4 py-3">Código</th>
                        <th class="px-4 py-3">{{ __('Nome') }}</th>
                        <th class="px-4 py-3">{{ __('Regional') }}</th>
                        <th class="px-4 py-3">{{ __('Administração') }}</th>
                        <th class="px-4 py-3">Local</th>
                        <th class="px-4 py-3">{{ __('Estado') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Ações') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($prayerHouses as $row)
                        <tr wire:key="ph-{{ $row->id }}">
                            <td class="px-4 py-3 font-mono text-xs text-slate-600 dark:text-slate-400">{{ $row->official_code ?? '—' }}</td>
                            <td class="px-4 py-3 font-medium">{{ $row->name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $row->administration?->regional?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $row->administration?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $row->location?->name ?? '—' }}</td>
                            <td class="px-4 py-3">
                                @if ($row->is_active)
                                    <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">Ativa</span>
                                @else
                                    <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-400">Inativa</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                @can('update', $row)
                                    <a href="{{ route('organization.prayer-houses.edit', $row) }}" wire:navigate class="text-primary-600 hover:underline">{{ __('Editar') }}</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">Nenhum registo.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $prayerHouses->links() }}</div>
    </x-ui.card>
</div>
