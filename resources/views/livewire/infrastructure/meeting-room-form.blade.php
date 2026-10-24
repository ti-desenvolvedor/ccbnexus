<x-ui.card title="{{ $this->meetingRoom ? 'Editar sala' : 'Nova sala' }}">
    <form wire:submit="save" data-progress="marked" class="grid w-full min-w-0 gap-4 md:grid-cols-2">
        <x-nexus.form-progress />
        <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Dono</label>
            <x-nexus.searchable-select
                wire:model.live="owner_type"
                data-progress-field
                class="mt-1"
                search-placeholder="{{ __('Buscar tipo...') }}"
                :options="[
                    ['value' => \App\Models\Administration::class, 'label' => __('Administração')],
                    ['value' => \App\Models\PrayerHouse::class, 'label' => __('Casa de oração')],
                ]"
            />
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Registo</label>
            <x-nexus.searchable-select
                wire:model.live="owner_id"
                data-progress-field
                class="mt-1"
                search-placeholder="{{ __('Buscar registo...') }}"
                :options="$owner_type === \App\Models\PrayerHouse::class
                    ? $prayerHouses->map(fn ($h) => ['value' => (string) $h->id, 'label' => $h->display_label])
                    : $administrations->map(fn ($a) => ['value' => (string) $a->id, 'label' => $a->name])"
            />
            @error('owner_id') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('Nome') }}</label>
            <input type="text" wire:model.live="name" data-progress-field class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
            @error('name') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Capacidade</label>
            <input type="number" wire:model="capacity" min="1" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
        </div>
        <div class="md:col-span-2">
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Local físico (opcional)</label>
            <x-nexus.searchable-select
                wire:model="location_id"
                class="mt-1"
                empty-label="—"
                search-placeholder="Buscar local..."
                :options="$locations->map(fn ($loc) => ['value' => (string) $loc->id, 'label' => $loc->name.' — '.$loc->formattedAddressSummary()])"
            />
        </div>
        <label class="inline-flex items-center gap-2 text-sm md:col-span-2"><input type="checkbox" wire:model="is_active" class="rounded" /> Ativa</label>
        <div class="flex gap-2 md:col-span-2">
            <x-ui.button type="submit">{{ __('Guardar') }}</x-ui.button>
            <a href="{{ route('infrastructure.meeting-rooms.index') }}" wire:navigate class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold dark:border-slate-800">{{ __('Cancelar') }}</a>
        </div>
    </form>
</x-ui.card>
