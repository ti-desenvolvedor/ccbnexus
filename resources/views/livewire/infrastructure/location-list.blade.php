<div class="space-y-4">
    <div class="flex flex-wrap items-end gap-3">
        <div class="min-w-[12rem] flex-1">
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('Pesquisar') }}</label>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Nome, endereço, cidade…" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
        </div>
        <div class="min-w-[10rem]">
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('Regional') }}</label>
            <x-nexus.searchable-select wire:model.live="regionalId" class="mt-1" empty-label="Todas" search-placeholder="{{ __('Buscar regional...') }}" :options="$regionals" />
        </div>
        <div class="min-w-[10rem]">
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('Administração') }}</label>
            <x-nexus.searchable-select wire:model.live="administrationId" class="mt-1" empty-label="Todas" search-placeholder="{{ __('Buscar administração...') }}" :options="$administrations" />
        </div>
    </div>
    <x-ui.card>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-600 dark:bg-slate-900 dark:text-slate-400">
                    <tr>
                        <th class="px-4 py-3">{{ __('Nome') }}</th>
                        <th class="px-4 py-3">{{ __('Administração') }}</th>
                        <th class="px-4 py-3">Endereço</th>
                        <th class="px-4 py-3 text-right">{{ __('Ações') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($locations as $row)
                        <tr wire:key="loc-{{ $row->id }}">
                            <td class="px-4 py-3 font-medium">{{ $row->name }}</td>
                            <td class="px-4 py-3 text-slate-600">
                                @if ($row->administration)
                                    {{ $row->administration->name }}
                                @elseif ($row->regional)
                                    {{ __('Sede') }} — {{ $row->regional->name }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $row->formattedAddressSummary() }}</td>
                            <td class="px-4 py-3 text-right">
                                @can('update', $row)
                                    <a href="{{ route('infrastructure.locations.edit', $row) }}" wire:navigate class="text-primary-600 hover:underline">{{ __('Editar') }}</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">Nenhum registo.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $locations->links() }}</div>
    </x-ui.card>
</div>
