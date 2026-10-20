<x-ui.card title="{{ __('Públicos (audiências legado)') }}">
    @include('livewire.agenda.partials.public-catalog-delete-error')
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase dark:bg-slate-900">
                <tr>
                    <th class="px-3 py-2">{{ __('Nome') }}</th>
                    <th class="px-3 py-2">Slug</th>
                    <th class="px-3 py-2 text-right">{{ __('Ações') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($audiences as $a)
                    <tr wire:key="au-{{ $a->id }}" class="border-t border-slate-100 dark:border-slate-800">
                        <td class="px-3 py-2 font-medium">{{ $a->name }}</td>
                        <td class="px-3 py-2 text-slate-600">{{ $a->slug }}</td>
                        <td class="px-3 py-2 text-right">
                            @can('update', $a)
                                <a href="{{ route('agenda.audiences.edit', $a) }}" wire:navigate class="text-primary-600 hover:underline">{{ __('Editar') }}</a>
                            @endcan
                            @can('delete', $a)
                                <button
                                    type="button"
                                    wire:click="deleteAudience({{ $a->id }})"
                                    wire:confirm="{{ __('Excluir esta audiência permanentemente?') }}"
                                    class="text-rose-600 hover:underline"
                                >{{ __('Excluir') }}</button>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $audiences->links() }}</div>
</x-ui.card>
