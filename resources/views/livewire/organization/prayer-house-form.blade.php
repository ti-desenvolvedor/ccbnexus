<x-ui.card title="{{ $this->prayerHouse ? 'Editar casa' : 'Nova casa' }}">
    <form wire:submit="save" data-progress="marked" class="grid w-full min-w-0 gap-4 md:grid-cols-2">
        <x-nexus.form-progress />
        <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('Administração') }}</label>
            <x-nexus.searchable-select
                wire:model.live="administration_id"
                data-progress-field
                class="mt-1"
                search-placeholder="{{ __('Buscar administração...') }}"
                :options="$administrations->map(fn ($a) => ['value' => (string) $a->id, 'label' => $a->name.' — '.($a->regional?->name ?? '')])"
            />
            @error('administration_id') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Código (BR)</label>
            <input type="text" wire:model="official_code" placeholder="BR 22-1637" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
            <p class="mt-1 text-xs text-slate-500">Formato: BR 22-1637 (opcional, único no sistema).</p>
            @error('official_code') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('Nome') }}</label>
            <input type="text" wire:model.live="name" data-progress-field class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
            @error('name') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
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
            @if ($locations->isEmpty() && $administration_id)
                <p class="mt-1 text-xs text-slate-500">
                    Nenhum local nesta administração.
                    <a href="{{ route('infrastructure.locations.create', ['administration_id' => $administration_id]) }}" wire:navigate class="text-primary-600 hover:underline">Cadastrar local</a>
                </p>
            @endif
        </div>
        <label class="inline-flex items-center gap-2 text-sm md:col-span-2"><input type="checkbox" wire:model="is_active" class="rounded" /> Ativa</label>
        <div class="flex gap-2 md:col-span-2">
            <x-ui.button type="submit">{{ __('Guardar') }}</x-ui.button>
            <a href="{{ route('organization.prayer-houses.index') }}" wire:navigate class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold dark:border-slate-800">{{ __('Cancelar') }}</a>
        </div>
    </form>
</x-ui.card>
