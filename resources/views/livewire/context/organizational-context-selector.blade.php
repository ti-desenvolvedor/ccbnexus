<div>
@if ($compact)
    <div class="grid grid-cols-1 gap-2 sm:flex sm:flex-wrap sm:items-center">
        <span class="text-xs font-semibold" style="color: rgb(var(--topbar-text) / 1);">{{ __('Contexto') }}</span>
        <x-nexus.searchable-select
            wire:model.live="regionalId"
            aria-label="{{ __('Regional') }}"
            class="w-full sm:min-w-[12rem] sm:flex-1"
            :empty-label="__('— escolher regional —')"
            :search-placeholder="__('Buscar regional...')"
            :options="$this->regionals"
        />
        <x-nexus.searchable-select
            wire:model.live="administrationId"
            aria-label="{{ __('Administração') }}"
            class="w-full sm:min-w-[10rem] sm:flex-1"
            :empty-label="__('Administração')"
            :search-placeholder="__('Buscar administração...')"
            :options="$this->administrations"
            :disabled="! $regionalId"
        />
        <x-nexus.searchable-select
            wire:model.live="prayerHouseId"
            aria-label="{{ __('Casa de oração') }}"
            class="w-full sm:min-w-[10rem] sm:flex-1"
            :empty-label="__('Casa')"
            :search-placeholder="__('Buscar casa...')"
            label-key="display_label"
            :options="$this->prayerHouses"
            :disabled="! $administrationId"
        />
        <x-ui.button type="button" wire:click="apply" size="sm" variant="secondary" class="min-h-11 w-full sm:w-auto" title="{{ __('O contexto já aplica ao mudar a regional; use se a página não atualizar.') }}">{{ __('Aplicar') }}</x-ui.button>
        @error('context')
            <p class="w-full text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>
@else
    <div class="flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-3 text-sm dark:border-slate-800 dark:bg-slate-950">
        <div class="min-w-[10rem] flex-1">
            <label for="context-regional" class="block text-xs font-semibold text-slate-700 dark:text-slate-200">{{ __('Regional ativa') }}</label>
            <x-nexus.searchable-select id="context-regional" wire:model.live="regionalId" class="mt-1" empty-label="—" search-placeholder="{{ __('Buscar regional...') }}" :options="$this->regionals" />
        </div>
        <div class="min-w-[10rem] flex-1">
            <label for="context-administration" class="block text-xs font-semibold text-slate-700 dark:text-slate-200">{{ __('Administração ativa') }}</label>
            <x-nexus.searchable-select id="context-administration" wire:model.live="administrationId" class="mt-1" empty-label="—" search-placeholder="{{ __('Buscar administração...') }}" :options="$this->administrations" :disabled="! $regionalId" />
        </div>
        <div class="min-w-[10rem] flex-1">
            <label for="context-house" class="block text-xs font-semibold text-slate-700 dark:text-slate-200">{{ __('Casa de oração ativa') }}</label>
            <x-nexus.searchable-select id="context-house" wire:model.live="prayerHouseId" class="mt-1" empty-label="—" search-placeholder="{{ __('Buscar casa...') }}" label-key="display_label" :options="$this->prayerHouses" :disabled="! $administrationId" />
        </div>
        <x-ui.button type="button" wire:click="apply" size="sm">{{ __('Aplicar contexto') }}</x-ui.button>
        @error('context')
            <p class="w-full text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>
@endif
</div>
