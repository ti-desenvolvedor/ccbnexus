<x-ui.card title="{{ __('Cargos / funções') }}">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase dark:bg-slate-900">
                <tr>
                    <th class="px-3 py-2">{{ __('Rótulo') }}</th>
                    <th class="px-3 py-2">{{ __('Coord.') }}</th>
                    <th class="px-3 py-2 text-right">{{ __('Ações') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($positions as $p)
                    <tr wire:key="pp-{{ $p->id }}" class="border-t border-slate-100 dark:border-slate-800">
                        <td class="px-3 py-2">{{ $p->labelForEventPicker() }}</td>
                        <td class="px-3 py-2 text-slate-600">{{ $p->is_department_coordinator ? __('Sim') : '—' }}</td>
                        <td class="px-3 py-2 text-right">
                            @can('update', $p)
                                <a href="{{ route('agenda.public-positions.edit', $p) }}" wire:navigate class="text-primary-600 hover:underline">{{ __('Editar') }}</a>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $positions->links() }}</div>
</x-ui.card>
