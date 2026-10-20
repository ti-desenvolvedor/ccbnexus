<div class="space-y-4">
    @include('livewire.agenda.partials.public-catalog-delete-error')
    <div class="flex flex-wrap gap-2 border-b border-slate-200 pb-3 dark:border-slate-800">
        <button
            type="button"
            wire:click="$set('scopeTab', '{{ \App\Models\PublicDepartment::SCOPE_REGIONAL }}')"
            class="rounded-lg px-3 py-1.5 text-sm font-semibold {{ $scopeTab === \App\Models\PublicDepartment::SCOPE_REGIONAL ? 'bg-primary-600 text-white' : 'bg-slate-100 text-slate-700 dark:bg-slate-900 dark:text-slate-300' }}"
        >{{ __('Regional') }}</button>
        <button
            type="button"
            wire:click="$set('scopeTab', '{{ \App\Models\PublicDepartment::SCOPE_ADMINISTRATION }}')"
            class="rounded-lg px-3 py-1.5 text-sm font-semibold {{ $scopeTab === \App\Models\PublicDepartment::SCOPE_ADMINISTRATION ? 'bg-primary-600 text-white' : 'bg-slate-100 text-slate-700 dark:bg-slate-900 dark:text-slate-300' }}"
        >{{ __('Administração') }}</button>
    </div>

    @can('create', \App\Models\PublicDepartment::class)
        <div class="flex justify-end">
            <a
                href="{{ route('agenda.public-departments.create', [
                    'scope' => $scopeTab,
                    'regional_id' => $group->regional_id,
                    'administration_id' => $scopeTab === \App\Models\PublicDepartment::SCOPE_ADMINISTRATION ? $group->regional->administrations()->where('is_active', true)->value('id') : null,
                    'public_subgroup_id' => $subgroup->id,
                ]) }}"
                wire:navigate
            >
                <x-ui.button size="sm">{{ __('Novo departamento neste grupo') }}</x-ui.button>
            </a>
        </div>
    @endcan

    <x-ui.card>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase dark:bg-slate-900">
                    <tr>
                        <th class="px-3 py-2 w-12">#</th>
                        <th class="px-3 py-2">{{ __('Nome') }}</th>
                        <th class="px-3 py-2">{{ __('Âmbito') }}</th>
                        <th class="px-3 py-2">{{ __('Âncora') }}</th>
                        <th class="px-3 py-2">{{ __('Cargos') }}</th>
                        <th class="px-3 py-2 text-right">{{ __('Ações') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($departments as $d)
                        <tr wire:key="pd-{{ $d->id }}" class="border-t border-slate-100 dark:border-slate-800">
                            <td class="px-3 py-2 text-slate-500">{{ $d->meta['catalog_number'] ?? '—' }}</td>
                            <td class="px-3 py-2 font-medium">{{ $d->name }}</td>
                            <td class="px-3 py-2">
                                <x-ui.badge tone="info">{{ $d->scope === \App\Models\PublicDepartment::SCOPE_REGIONAL ? __('Regional') : __('Administração') }}</x-ui.badge>
                            </td>
                            <td class="px-3 py-2 text-slate-600 dark:text-slate-400">
                                @if ($d->scope === \App\Models\PublicDepartment::SCOPE_REGIONAL)
                                    {{ $d->regional?->name }}
                                @else
                                    {{ $d->administration?->name }}
                                @endif
                            </td>
                            <td class="px-3 py-2">{{ $d->positions_count }}</td>
                            <td class="px-3 py-2 text-right">
                                @can('update', $d)
                                    <a href="{{ route('agenda.public-departments.edit', $d) }}" wire:navigate class="text-primary-600 hover:underline">{{ __('Editar') }}</a>
                                @endcan
                                @can('delete', $d)
                                    <button
                                        type="button"
                                        wire:click="deletePublicDepartment({{ $d->id }})"
                                        wire:confirm="{{ __('Excluir este departamento? Esta ação não pode ser desfeita.') }}"
                                        class="text-rose-600 hover:underline"
                                    >{{ __('Excluir') }}</button>
                                    @if ($d->positions_count > 0)
                                        <button
                                            type="button"
                                            wire:click="deletePublicDepartment({{ $d->id }}, true)"
                                            wire:confirm="{{ __('Excluir departamento e cargos vinculados?') }}"
                                            class="text-rose-700 hover:underline"
                                        >{{ __('Excluir com cargos') }}</button>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-6 text-center text-slate-500">{{ __('Sem departamentos.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $departments->links() }}</div>
    </x-ui.card>
</div>
