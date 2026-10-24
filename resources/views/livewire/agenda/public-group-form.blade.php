<x-ui.card title="{{ $this->publicGroup ? __('Editar grupo') : __('Novo grupo') }}">
    <form wire:submit="save" data-progress="marked" class="grid w-full min-w-0 gap-4 md:grid-cols-2">
        <x-nexus.form-progress />
        <div class="grid gap-4 md:col-span-2 lg:grid-cols-3">
            <div>
                <label class="text-xs font-semibold text-slate-500">{{ __('Regional') }}</label>
                <x-nexus.searchable-select wire:model="regional_id" data-progress-field class="mt-1" :search-placeholder="__('Buscar regional...')" :options="$regionals" />
                @error('regional_id') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-500">{{ __('Nome') }}</label>
                <input type="text" wire:model.live="name" data-progress-field class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
                @error('name') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-500">{{ __('Ordem') }}</label>
                <input type="number" wire:model="sort_order" min="0" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
            </div>
        </div>
        <label class="flex items-center gap-2 text-sm md:col-span-2"><input type="checkbox" wire:model="is_active" class="rounded" /> {{ __('Ativo') }}</label>
        <div class="flex flex-wrap gap-2 md:col-span-2">
            <x-ui.button type="submit">{{ __('Guardar') }}</x-ui.button>
            <a href="{{ route('agenda.public-groups.index') }}" wire:navigate class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold dark:border-slate-800">{{ __('Cancelar') }}</a>
        </div>
    </form>

    @include('livewire.agenda.partials.public-catalog-form-delete-zone', [
        'record' => $this->publicGroup,
        'confirmMessage' => __('Excluir este grupo permanentemente? Só é possível se não houver subgrupos.'),
    ])
</x-ui.card>
