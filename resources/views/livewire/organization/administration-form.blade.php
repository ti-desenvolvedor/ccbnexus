<x-ui.card title="{{ $this->administration ? 'Editar' : 'Nova' }} administração">
    <form wire:submit="save" class="grid max-w-xl gap-4">
        <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('Regional') }}</label>
            <x-nexus.searchable-select wire:model.live="regional_id" class="mt-1" :search-placeholder="__('Buscar regional...')" :options="$regionals" />
            @error('regional_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('Nome') }}</label>
            <input type="text" wire:model.live="name" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
            @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Local (opcional)</label>
            <x-nexus.searchable-select
                wire:model="location_id"
                class="mt-1"
                empty-label="—"
                search-placeholder="Buscar local..."
                :options="$locations->map(fn ($loc) => ['value' => (string) $loc->id, 'label' => $loc->name.' — '.$loc->formattedAddressSummary()])"
            />
        </div>
        <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" wire:model="is_active" class="rounded" /> Ativa</label>
        <div class="flex gap-2">
            <x-ui.button type="submit">{{ __('Guardar') }}</x-ui.button>
            <a href="{{ route('organization.administrations.index') }}" wire:navigate class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold dark:border-slate-800">{{ __('Cancelar') }}</a>
        </div>
    </form>
</x-ui.card>
