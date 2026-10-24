<x-ui.card title="{{ $this->regional ? 'Editar regional' : 'Nova regional' }}">
    <form wire:submit="save" class="grid w-full min-w-0 gap-4 md:grid-cols-2">
        <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('Nome') }}</label>
            <input type="text" wire:model.live="name" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
            @error('name') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Local (opcional)</label>
            <x-nexus.searchable-select wire:model="location_id" class="mt-1" empty-label="—" search-placeholder="Buscar local..." :options="$locations" />
            @error('location_id') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
        </div>
        <label class="inline-flex items-center gap-2 text-sm md:col-span-2">
            <input type="checkbox" wire:model="is_active" class="rounded border-slate-300" />
            Ativa
        </label>
        <div class="flex gap-2 md:col-span-2">
            <x-ui.button type="submit">{{ __('Guardar') }}</x-ui.button>
            <a href="{{ route('organization.regionals.index') }}" wire:navigate class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold dark:border-slate-800">{{ __('Cancelar') }}</a>
        </div>
    </form>
</x-ui.card>
