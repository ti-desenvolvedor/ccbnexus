<x-ui.card title="{{ __('Departamentos (âmbito)') }}">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase dark:bg-slate-900">
                <tr>
                    <th class="px-3 py-2">{{ __('Âmbito') }}</th>
                    <th class="px-3 py-2">{{ __('Âncora') }}</th>
                    <th class="px-3 py-2">{{ __('Nome') }}</th>
                    <th class="px-3 py-2">{{ __('Slug') }}</th>
                    <th class="px-3 py-2 text-right">{{ __('Ações') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($departments as $d)
                    <tr wire:key="pd-{{ $d->id }}" class="border-t border-slate-100 dark:border-slate-800">
                        <td class="px-3 py-2">{{ $d->scope }}</td>
                        <td class="px-3 py-2 text-slate-600">
                            @if ($d->scope === \App\Models\PublicDepartment::SCOPE_REGIONAL)
                                {{ $d->regional?->name }}
                            @elseif ($d->scope === \App\Models\PublicDepartment::SCOPE_ADMINISTRATION)
                                {{ $d->administration?->name }}
                            @else
                                {{ $d->prayerHouse?->name }}
                            @endif
                        </td>
                        <td class="px-3 py-2 font-medium">{{ $d->name }}</td>
                        <td class="px-3 py-2 text-slate-600">{{ $d->slug }}</td>
                        <td class="px-3 py-2 text-right">
                            @can('update', $d)
                                <a href="{{ route('agenda.public-departments.edit', $d) }}" wire:navigate class="text-primary-600 hover:underline">{{ __('Editar') }}</a>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $departments->links() }}</div>
</x-ui.card>
