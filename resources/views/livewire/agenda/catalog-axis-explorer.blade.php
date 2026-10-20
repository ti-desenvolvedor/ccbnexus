@php
    $axisDescriptions = [
        'operational' => __('Sete departamentos em regional, administração e casa de oração (tesouraria operacional distinta da estatutária).'),
        'ministry' => __('Departamento ministerio com quatro cargos por âmbito regional e por administração.'),
        'music' => __('Carteiras regionais, encarregados por administração (1–2) e cargos locais na casa.'),
    ];
    $allowsHouse = in_array($axis, ['operational', 'music'], true);
@endphp

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
                :options="collect([
                    ['value' => \App\Models\PublicDepartment::SCOPE_REGIONAL, 'label' => __('Regional')],
                    ['value' => \App\Models\PublicDepartment::SCOPE_ADMINISTRATION, 'label' => __('Administração')],
                ])->when($allowsHouse, fn ($options) => $options->push(['value' => \App\Models\PublicDepartment::SCOPE_PRAYER_HOUSE, 'label' => __('Casa de oração')]))"
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
        @if ($allowsHouse && $scopeFilter === \App\Models\PublicDepartment::SCOPE_PRAYER_HOUSE)
            <div class="min-w-[12rem] flex-1">
                <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('Casa de oração') }}</label>
                <x-nexus.searchable-select
                    wire:model.live="prayerHouseId"
                    class="mt-1"
                    :empty-label="__('Todas as casas')"
                    :search-placeholder="__('Buscar casa...')"
                    :options="$houses->map(fn ($h) => ['value' => (string) $h->id, 'label' => $h->name.($h->is_active ? '' : ' ('.__('inativa').')')])"
                />
            </div>
        @endif
    </div>

    @if (! $regionalId)
        <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('Selecione uma regional para explorar o catálogo.') }}</p>
    @else
        <x-ui.card>
            <p class="mb-4 text-sm text-slate-600 dark:text-slate-300">
                {{ $axisDescriptions[$axis] ?? '' }}
            </p>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase dark:bg-slate-900">
                        <tr>
                            <th class="px-3 py-2">{{ __('Departamento') }}</th>
                            <th class="px-3 py-2">{{ __('Âmbito') }}</th>
                            <th class="px-3 py-2">{{ __('Cargos') }}</th>
                            <th class="px-3 py-2 text-right">{{ __('Ações') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($structure as $row)
                            @php $d = $row['department']; @endphp
                            <tr wire:key="axis-dept-{{ $d->id }}" class="border-t border-slate-100 dark:border-slate-800">
                                <td class="px-3 py-2 font-medium">{{ $d->name }}</td>
                                <td class="px-3 py-2">
                                    <x-ui.badge tone="info">
                                        @if ($d->scope === \App\Models\PublicDepartment::SCOPE_REGIONAL)
                                            {{ __('Regional') }}
                                        @elseif ($d->scope === \App\Models\PublicDepartment::SCOPE_ADMINISTRATION)
                                            {{ $d->administration?->name ?? __('Administração') }}
                                        @else
                                            {{ $d->prayerHouse?->name ?? __('Casa de oração') }}
                                        @endif
                                    </x-ui.badge>
                                </td>
                                <td class="px-3 py-2">{{ $row['positions']->count() }}</td>
                                <td class="space-x-2 px-3 py-2 text-right">
                                    <a href="{{ route('agenda.public-departments.edit', $d) }}" wire:navigate class="text-xs text-primary-600 hover:underline">{{ __('Editar') }}</a>
                                    <a href="{{ route('agenda.public-positions.index', ['department' => $d->id]) }}" wire:navigate class="text-xs text-slate-600 hover:underline dark:text-slate-400">{{ __('Cargos') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-6 text-center text-slate-500">
                                    {{ __('Nenhum departamento para este filtro. Execute php artisan public-catalog:rebuild --force ou ajuste o âmbito.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    @endif
</div>
