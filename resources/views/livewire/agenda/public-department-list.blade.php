<div class="space-y-4">
    @include('livewire.agenda.partials.public-catalog-delete-error')

    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/40">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="block text-xs font-semibold text-slate-500">{{ __('Pesquisar') }}</label>
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="{{ __('Nome…') }}" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950" />
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500">{{ __('Regional') }}</label>
                <x-nexus.searchable-select wire:model.live="regionalId" class="mt-1" :empty-label="__('Todas')" :search-placeholder="__('Buscar regional...')" :options="$regionals" />
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500">{{ __('Âmbito') }}</label>
                <x-nexus.searchable-select
                    wire:model.live="scopeFilter"
                    class="mt-1"
                    :empty-label="__('Todos')"
                    :search-placeholder="__('Buscar âmbito...')"
                    :options="[
                        ['value' => \App\Models\PublicDepartment::SCOPE_REGIONAL, 'label' => __('Regional')],
                        ['value' => \App\Models\PublicDepartment::SCOPE_ADMINISTRATION, 'label' => __('Administração')],
                        ['value' => \App\Models\PublicDepartment::SCOPE_PRAYER_HOUSE, 'label' => __('Casa de oração')],
                    ]"
                />
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500">{{ __('Grupo I–V') }}</label>
                <x-nexus.searchable-select wire:model.live="subgroupId" class="mt-1" :empty-label="__('Todos')" :search-placeholder="__('Buscar grupo...')" :options="$subgroups" :disabled="$subgroups->isEmpty()" />
            </div>
        </div>
        <div class="mt-3 flex flex-wrap items-center gap-4">
            @if ($regionalId !== '' && $administrations->isNotEmpty())
                <div class="min-w-[12rem] flex-1">
                    <label class="block text-xs font-semibold text-slate-500">{{ __('Administração') }}</label>
                    <x-nexus.searchable-select
                        wire:model.live="administrationId"
                        class="mt-1"
                        :empty-label="__('Todas')"
                        :search-placeholder="__('Buscar administração...')"
                        :options="$administrations->map(fn ($a) => ['value' => (string) $a->id, 'label' => $a->name.($a->is_active ? '' : ' ('.__('inativa').')')])"
                    />
                </div>
            @endif
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model.live="catalogAdministrativeOnly" class="rounded" />
                {{ __('Só catálogo administrativo (Grupos I–V)') }}
            </label>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model.live="grouped" class="rounded" />
                {{ __('Agrupar por grupo') }}
            </label>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model.live="activeOnly" class="rounded" />
                {{ __('Só ativos') }}
            </label>
        </div>
    </div>

    <x-ui.card>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase dark:bg-slate-900">
                    <tr>
                        <th class="px-3 py-2 w-12">#</th>
                        <th class="px-3 py-2">{{ __('Grupo') }}</th>
                        <th class="px-3 py-2">{{ __('Nome') }}</th>
                        <th class="px-3 py-2">{{ __('Âmbito') }}</th>
                        <th class="px-3 py-2">{{ __('Âncora') }}</th>
                        <th class="px-3 py-2">{{ __('Cargos') }}</th>
                        <th class="px-3 py-2">{{ __('Estado') }}</th>
                        <th class="px-3 py-2 text-right">{{ __('Ações') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($grouped && $groupedDepartments && $groupedDepartments->isNotEmpty())
                        @foreach ($groupedDepartments as $groupName => $items)
                            <tr class="bg-slate-100 dark:bg-slate-900">
                                <td colspan="8" class="px-3 py-2 text-xs font-bold uppercase tracking-wide text-slate-600 dark:text-slate-400">{{ $groupName }}</td>
                            </tr>
                            @foreach ($items as $d)
                                @include('livewire.agenda.partials.public-department-row', ['d' => $d])
                            @endforeach
                        @endforeach
                    @elseif ($grouped)
                        <tr>
                            <td colspan="8" class="px-3 py-6 text-center text-slate-500">{{ __('Sem departamentos com os filtros atuais.') }}</td>
                        </tr>
                    @elseif ($departments)
                        @forelse ($departments as $d)
                            @include('livewire.agenda.partials.public-department-row', ['d' => $d])
                        @empty
                            <tr>
                                <td colspan="8" class="px-3 py-6 text-center text-slate-500">{{ __('Sem departamentos.') }}</td>
                            </tr>
                        @endforelse
                    @endif
                </tbody>
            </table>
        </div>
        @if (! $grouped && $departments)
            <div class="mt-4">{{ $departments->links() }}</div>
        @endif
    </x-ui.card>
</div>
