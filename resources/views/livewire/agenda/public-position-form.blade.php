<x-ui.card title="{{ $this->position ? __('Editar cargo') : __('Novo cargo') }}">
    <form wire:submit="save" class="grid max-w-lg gap-4">
        <div>
            <label class="text-xs font-semibold text-slate-500">{{ __('Departamento') }}</label>
            <x-nexus.searchable-select
                wire:model="public_department_id"
                class="mt-1"
                :search-placeholder="__('Buscar departamento...')"
                :options="collect([['value' => '0', 'label' => '—']])->merge($departments->map(fn ($d) => ['value' => (string) $d->id, 'label' => $d->name.' ('.$d->scope.')']))"
            />
            @error('public_department_id') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-500">{{ __('Subgrupo (opcional)') }}</label>
            <x-nexus.searchable-select
                wire:model="public_subgroup_id"
                class="mt-1"
                :empty-label="__('— nenhum —')"
                :search-placeholder="__('Buscar subgrupo...')"
                :options="$subgroups->map(fn ($s) => ['value' => (string) $s->id, 'label' => ($s->group?->name ?? '').' › '.$s->name])"
            />
            @error('public_subgroup_id') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-500">{{ __('Nome do cargo') }}</label>
            <input type="text" wire:model.live="name" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
            @error('name') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-500">{{ __('Ordem') }}</label>
            <input type="number" wire:model="sort_order" min="0" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
        </div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="is_active" class="rounded" /> {{ __('Ativo') }}</label>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" wire:model="is_department_coordinator" class="rounded" />
            {{ __('Coordenador de departamento') }}
        </label>
        <p class="text-xs text-slate-500">{{ __('Marque quando o cargo for o responsável pela articulação do departamento (ex.: colaboração administrativa que compõe a regional).') }}</p>
        <div class="flex gap-2">
            <x-ui.button type="submit">{{ __('Guardar') }}</x-ui.button>
            <a href="{{ route('agenda.public-positions.index') }}" wire:navigate class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold dark:border-slate-800">{{ __('Cancelar') }}</a>
        </div>
    </form>

    @include('livewire.agenda.partials.public-catalog-form-delete-zone', [
        'record' => $this->position,
        'confirmMessage' => __('Excluir este cargo permanentemente?'),
    ])
</x-ui.card>
