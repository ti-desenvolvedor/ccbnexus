<x-ui.card title="{{ $this->department ? __('Editar departamento') : __('Novo departamento') }}">
    <form wire:submit="save" data-progress="marked" class="grid w-full min-w-0 gap-6 md:grid-cols-2">
        <x-nexus.form-progress class="md:col-span-2" />
        <div class="space-y-4 md:col-span-2">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300">{{ __('Âmbito') }}</h3>
            <x-nexus.searchable-select
                data-progress-field
                wire:model.live="scope"
                :search-placeholder="__('Buscar âmbito...')"
                :options="[
                    ['value' => \App\Models\PublicDepartment::SCOPE_REGIONAL, 'label' => __('Regional')],
                    ['value' => \App\Models\PublicDepartment::SCOPE_ADMINISTRATION, 'label' => __('Administração')],
                    ['value' => \App\Models\PublicDepartment::SCOPE_PRAYER_HOUSE, 'label' => __('Casa de oração')],
                ]"
            />
        </div>

        @if ($scope === \App\Models\PublicDepartment::SCOPE_REGIONAL)
            <div class="md:col-span-2">
                <label class="text-xs font-semibold text-slate-500">{{ __('Regional') }}</label>
                <x-nexus.searchable-select wire:model.live="regional_id" data-progress-field class="mt-1" :search-placeholder="__('Buscar regional...')" :options="$regionals" />
                @error('regional_id') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
            </div>
        @endif

        @if ($scope === \App\Models\PublicDepartment::SCOPE_ADMINISTRATION)
            <div class="md:col-span-2">
                <label class="text-xs font-semibold text-slate-500">{{ __('Administração') }}</label>
                <x-nexus.searchable-select
                    wire:model.live="administration_id"
                    data-progress-field
                    class="mt-1"
                    empty-label="—"
                    :search-placeholder="__('Buscar administração...')"
                    :options="$administrations->map(fn ($a) => ['value' => (string) $a->id, 'label' => $a->name.' ('.($a->regional?->name ?? '').')'])"
                />
                @error('administration_id') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
            </div>
        @endif

        @if ($scope === \App\Models\PublicDepartment::SCOPE_PRAYER_HOUSE)
            <div class="md:col-span-2">
                <label class="text-xs font-semibold text-slate-500">{{ __('Casa de oração') }}</label>
                <x-nexus.searchable-select wire:model="prayer_house_id" data-progress-field class="mt-1" empty-label="—" :search-placeholder="__('Buscar casa...')" label-key="display_label" :options="$prayerHouses" />
                @error('prayer_house_id') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
            </div>
        @endif

        @if (in_array($scope, [\App\Models\PublicDepartment::SCOPE_REGIONAL, \App\Models\PublicDepartment::SCOPE_ADMINISTRATION], true))
            <div class="md:col-span-2">
                <h3 class="mb-2 text-sm font-semibold text-slate-700 dark:text-slate-300">{{ __('Classificação') }}</h3>
                <label class="text-xs font-semibold text-slate-500">{{ __('Subgrupo (Grupo I–V)') }}</label>
                <x-nexus.searchable-select wire:model="public_subgroup_id" data-progress-field class="mt-1" :empty-label="__('Selecionar')" :search-placeholder="__('Buscar subgrupo...')" :options="$adminSubgroups" />
                @error('public_subgroup_id') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
            </div>
            @if ($this->department?->meta['catalog_number'] ?? null)
                <div class="md:col-span-2">
                    <label class="text-xs font-semibold text-slate-500">{{ __('Número no catálogo') }}</label>
                    <input type="text" readonly value="{{ $this->department->meta['catalog_number'] }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-900" />
                </div>
            @endif
        @endif

        <div>
            <label class="text-xs font-semibold text-slate-500">{{ __('Nome') }}</label>
            <input type="text" wire:model.live="name" data-progress-field class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
            @error('name') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-500">{{ __('Ordem') }}</label>
            <input type="number" wire:model="sort_order" min="0" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
        </div>
        <div class="space-y-1 md:col-span-2">
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="is_active" class="rounded" /> {{ __('Ativo') }}</label>
            <p class="text-xs text-slate-500">{{ __('Desativar oculta o departamento; para apagar um registo criado por engano, use a exclusão permanente no final da página.') }}</p>
        </div>
        <div class="flex gap-2 md:col-span-2">
            <x-ui.button type="submit">{{ __('Guardar') }}</x-ui.button>
            <a href="{{ route('agenda.public-departments.index') }}" wire:navigate class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold dark:border-slate-800">{{ __('Cancelar') }}</a>
        </div>
    </form>

    @include('livewire.agenda.partials.public-catalog-form-delete-zone', [
        'record' => $this->department,
        'confirmMessage' => __('Excluir este departamento permanentemente?'),
    ])

    @if ($this->department && $this->department->positions()->exists())
        @can('delete', $this->department)
            <div class="mt-3">
                <button
                    type="button"
                    wire:click="destroyRecord(true)"
                    wire:confirm="{{ __('Excluir departamento e todos os cargos vinculados? Esta ação não pode ser desfeita.') }}"
                    class="rounded-lg border border-rose-400 px-4 py-2 text-sm font-semibold text-rose-800 hover:bg-rose-50 dark:border-rose-700 dark:text-rose-300"
                >{{ __('Excluir com cargos vinculados') }}</button>
            </div>
        @endcan
    @endif
</x-ui.card>
