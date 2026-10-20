<x-ui.card title="{{ __('Grupos de público') }}">
    @include('livewire.agenda.partials.public-catalog-delete-error')
    <div class="mb-4 flex flex-wrap items-end gap-3">
        <div>
            <label class="text-xs font-semibold text-slate-500">{{ __('Regional') }}</label>
            <x-nexus.searchable-select wire:model.live="filter_regional_id" class="mt-1 min-w-[12rem]" :empty-label="__('Todas')" :search-placeholder="__('Buscar regional...')" :options="$regionals" />
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase dark:bg-slate-900">
                <tr>
                    <th class="px-3 py-2">{{ __('Regional') }}</th>
                    <th class="px-3 py-2">{{ __('Nome') }}</th>
                    <th class="px-3 py-2">{{ __('Slug') }}</th>
                    <th class="px-3 py-2 text-right">{{ __('Ações') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($groups as $g)
                    <tr wire:key="pg-{{ $g->id }}" class="border-t border-slate-100 dark:border-slate-800">
                        <td class="px-3 py-2 text-slate-600">{{ $g->regional?->name }}</td>
                        <td class="px-3 py-2 font-medium">{{ $g->name }}</td>
                        <td class="px-3 py-2 text-slate-600">{{ $g->slug }}</td>
                        <td class="px-3 py-2 text-right space-x-2">
                            @can('update', $g)
                                <a href="{{ route('agenda.public-groups.edit', $g) }}" wire:navigate class="text-primary-600 hover:underline">{{ __('Editar') }}</a>
                                <a href="{{ route('agenda.public-subgroups.index', $g) }}" wire:navigate class="text-primary-600 hover:underline">{{ __('Subgrupos') }}</a>
                            @endcan
                            @can('delete', $g)
                                <button
                                    type="button"
                                    wire:click="deletePublicGroup({{ $g->id }})"
                                    wire:confirm="{{ __('Excluir este grupo? Só é possível se não houver subgrupos. Esta ação não pode ser desfeita.') }}"
                                    class="text-rose-600 hover:underline"
                                >{{ __('Excluir') }}</button>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $groups->links() }}</div>
</x-ui.card>
