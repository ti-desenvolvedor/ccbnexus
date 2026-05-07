<x-ui.card title="{{ __('Subgrupos') }} — {{ $group->name }}">
    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('agenda.public-groups.index') }}" wire:navigate class="text-sm text-primary-600 hover:underline">{{ __('← Grupos') }}</a>
        @can('create', \App\Models\PublicSubgroup::class)
            <a href="{{ route('agenda.public-subgroups.create', $group) }}" wire:navigate><x-ui.button type="button">{{ __('Novo subgrupo') }}</x-ui.button></a>
        @endcan
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase dark:bg-slate-900">
                <tr>
                    <th class="px-3 py-2">{{ __('Nome') }}</th>
                    <th class="px-3 py-2">{{ __('Slug') }}</th>
                    <th class="px-3 py-2 text-right">{{ __('Ações') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($subgroups as $s)
                    <tr wire:key="psg-{{ $s->id }}" class="border-t border-slate-100 dark:border-slate-800">
                        <td class="px-3 py-2 font-medium">{{ $s->name }}</td>
                        <td class="px-3 py-2 text-slate-600">{{ $s->slug }}</td>
                        <td class="px-3 py-2 text-right">
                            @can('update', $s)
                                <a href="{{ route('agenda.public-subgroups.edit', [$group, $s]) }}" wire:navigate class="text-primary-600 hover:underline">{{ __('Editar') }}</a>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $subgroups->links() }}</div>
</x-ui.card>
