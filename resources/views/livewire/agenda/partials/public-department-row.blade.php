<tr wire:key="pd-{{ $d->id }}" class="border-t border-slate-100 dark:border-slate-800">
    <td class="px-3 py-2 text-slate-500">{{ $d->meta['catalog_number'] ?? '—' }}</td>
    <td class="px-3 py-2 text-slate-600 dark:text-slate-400">{{ $d->subgroup?->name ?? '—' }}</td>
    <td class="px-3 py-2 font-medium">{{ $d->name }}</td>
    <td class="px-3 py-2">
        @php
            $scopeLabel = match ($d->scope) {
                \App\Models\PublicDepartment::SCOPE_REGIONAL => __('Regional'),
                \App\Models\PublicDepartment::SCOPE_ADMINISTRATION => __('Administração'),
                default => __('Casa de oração'),
            };
        @endphp
        <x-ui.badge tone="info">{{ $scopeLabel }}</x-ui.badge>
    </td>
    <td class="px-3 py-2 text-slate-600 dark:text-slate-400">
        @if ($d->scope === \App\Models\PublicDepartment::SCOPE_REGIONAL)
            {{ $d->regional?->name }}
        @elseif ($d->scope === \App\Models\PublicDepartment::SCOPE_ADMINISTRATION)
            {{ $d->administration?->name }}
        @else
            {{ $d->prayerHouse?->name }}
        @endif
    </td>
    <td class="px-3 py-2">{{ $d->positions_count }}</td>
    <td class="px-3 py-2">
        <x-ui.badge :tone="$d->is_active ? 'success' : 'neutral'">
            {{ $d->is_active ? __('Ativo') : __('Inativo') }}
        </x-ui.badge>
    </td>
    <td class="px-3 py-2 text-right space-x-2">
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
                    wire:confirm="{{ __('Excluir departamento e todos os cargos vinculados?') }}"
                    class="text-rose-700 hover:underline"
                >{{ __('Excluir com cargos') }}</button>
            @endif
        @endcan
    </td>
</tr>
