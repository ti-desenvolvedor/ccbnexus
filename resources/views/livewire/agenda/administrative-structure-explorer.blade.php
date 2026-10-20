<div class="space-y-6">
    @include('livewire.agenda.partials.public-catalog-delete-error')
    <div class="flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/40">
        <div class="min-w-[12rem] flex-1">
            <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('Regional') }}</label>
            <x-nexus.searchable-select wire:model.live="regionalId" class="mt-1" :empty-label="__('Selecionar')" :search-placeholder="__('Buscar regional...')" :options="$regionals" />
        </div>
        <div class="min-w-[10rem]">
            <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('Âmbito') }}</label>
            <x-nexus.searchable-select
                wire:model.live="scopeFilter"
                class="mt-1"
                :search-placeholder="__('Buscar âmbito...')"
                :options="[
                    ['value' => \App\Models\PublicDepartment::SCOPE_REGIONAL, 'label' => __('Regional')],
                    ['value' => \App\Models\PublicDepartment::SCOPE_ADMINISTRATION, 'label' => __('Administração')],
                ]"
            />
        </div>
        @if ($scopeFilter === \App\Models\PublicDepartment::SCOPE_ADMINISTRATION)
            <div class="min-w-[12rem] flex-1">
                <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('Administração') }}</label>
                <x-nexus.searchable-select
                    wire:model.live="administrationId"
                    class="mt-1"
                    :empty-label="__('Todas as administrações')"
                    :search-placeholder="__('Buscar administração...')"
                    :options="$administrations->map(fn ($a) => ['value' => (string) $a->id, 'label' => $a->name.($a->is_active ? '' : ' ('.__('inativa').')')])"
                />
            </div>
        @endif
    </div>

    @if (! $regionalId)
        <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('Selecione uma regional para ver a estrutura administrativa.') }}</p>
    @else
        <div class="space-y-4">
            @foreach ($structure as $block)
                @php
                    $subgroup = $block['subgroup'];
                    $subgroup->loadMissing('group');
                    $adminGroup = $subgroup->group;
                @endphp
                <x-ui.card>
                    <x-slot name="toolbar">
                        @if ($adminGroup)
                            <a
                                href="{{ route('agenda.public-subgroup.departments', ['group' => $adminGroup, 'subgroup' => $subgroup]) }}"
                                wire:navigate
                                class="text-xs font-semibold text-primary-600 hover:underline"
                            >{{ __('Ver listagem') }}</a>
                        @endif
                    </x-slot>
                    <div class="mb-4 font-semibold text-slate-900 dark:text-slate-100">{{ $subgroup->name }}</div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase dark:bg-slate-900">
                                <tr>
                                    <th class="px-3 py-2 w-12">#</th>
                                    <th class="px-3 py-2">{{ __('Departamento') }}</th>
                                    <th class="px-3 py-2">{{ __('Estado') }}</th>
                                    <th class="px-3 py-2">{{ __('Paridade') }}</th>
                                    <th class="px-3 py-2 text-right">{{ __('Ações') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($block['departments'] as $row)
                                    @php $d = $row['department']; @endphp
                                    <tr wire:key="dept-{{ $d->id }}" class="border-t border-slate-100 dark:border-slate-800">
                                        <td class="px-3 py-2 text-slate-500">{{ $d->meta['catalog_number'] ?? '—' }}</td>
                                        <td class="px-3 py-2 font-medium">{{ $d->name }}</td>
                                        <td class="px-3 py-2">
                                            <x-ui.badge :tone="$d->is_active ? 'success' : 'neutral'">
                                                {{ $d->is_active ? __('Ativo') : __('Inativo') }}
                                            </x-ui.badge>
                                        </td>
                                        <td class="px-3 py-2 text-xs text-slate-600 dark:text-slate-400">
                                            @if ($row['has_regional_pair'] && $row['has_admin_pair'])
                                                ✓ R / ✓ A
                                            @elseif ($row['has_regional_pair'])
                                                ✓ {{ __('Regional') }}
                                            @elseif ($row['has_admin_pair'])
                                                ✓ {{ __('Administração') }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-right space-x-2">
                                            <a href="{{ route('agenda.public-positions.index', ['department' => $d->id]) }}" wire:navigate class="text-xs text-slate-600 hover:underline dark:text-slate-400">{{ __('Cargos') }} ({{ $row['positions_count'] }})</a>
                                            @can('update', $d)
                                                <a href="{{ route('agenda.public-departments.edit', $d) }}" wire:navigate class="text-xs text-primary-600 hover:underline">{{ __('Editar') }}</a>
                                            @endcan
                                            @can('delete', $d)
                                                <button
                                                    type="button"
                                                    wire:click="deletePublicDepartment({{ $d->id }})"
                                                    wire:confirm="{{ __('Excluir este departamento? Esta ação não pode ser desfeita.') }}"
                                                    class="text-xs text-rose-600 hover:underline"
                                                >{{ __('Excluir') }}</button>
                                                @if ($row['positions_count'] > 0)
                                                    <button
                                                        type="button"
                                                        wire:click="deletePublicDepartment({{ $d->id }}, true)"
                                                        wire:confirm="{{ __('Excluir departamento e cargos vinculados?') }}"
                                                        class="text-xs text-rose-700 hover:underline"
                                                    >{{ __('Excluir com cargos') }}</button>
                                                @endif
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-3 py-4 text-center text-slate-500">{{ __('Sem departamentos neste grupo.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-ui.card>
            @endforeach
        </div>
    @endif
</div>
