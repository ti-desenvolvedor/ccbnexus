<div>
@if ($compact)
    @php
        $regionals = $this->regionals;
        $administrations = $this->administrations;
        $prayerHouses = $this->prayerHouses;
        $summary = collect([
            $regionals->firstWhere('id', (int) $regionalId)?->name,
            $administrations->firstWhere('id', (int) $administrationId)?->name,
            $prayerHouses->firstWhere('id', (int) $prayerHouseId)?->display_label,
        ])->filter()->implode(' · ');
    @endphp
    @php($contextOpen = $errors->has('context') || ! $regionalId)
    <div x-data="{ open: @js($contextOpen) }">
        <button
            type="button"
            class="flex min-h-11 w-full items-center gap-2 rounded-lg text-left text-sm md:hidden"
            style="color: rgb(var(--topbar-text) / 1);"
            x-on:click="open = !open"
            x-bind:aria-expanded="open"
            aria-controls="context-fields"
        >
            <span class="shrink-0 text-xs font-semibold">{{ __('Contexto') }}</span>
            <span class="min-w-0 flex-1 truncate" style="color: rgb(var(--topbar-muted) / 1);">{{ $summary !== '' ? $summary : __('Escolha a regional') }}</span>
            <svg class="h-4 w-4 shrink-0 transition" x-bind:class="{ 'rotate-180': open }" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-width="2" stroke-linecap="round" d="M6 9l6 6 6-6" /></svg>
        </button>
    <div id="context-fields" @class(['grid grid-cols-1 gap-2 pb-1 sm:flex sm:flex-wrap sm:items-center md:pb-0', 'max-md:hidden' => ! $contextOpen]) x-bind:class="{ 'max-md:hidden': !open }">
        <span class="hidden text-xs font-semibold md:inline" style="color: rgb(var(--topbar-text) / 1);">{{ __('Contexto') }}</span>
        <x-nexus.searchable-select
            wire:model.live="regionalId"
            aria-label="{{ __('Regional') }}"
            class="w-full sm:min-w-[12rem] sm:flex-1"
            :empty-label="__('— escolher regional —')"
            :search-placeholder="__('Buscar regional...')"
            :options="$regionals"
        />
        <x-nexus.searchable-select
            wire:model.live="administrationId"
            aria-label="{{ __('Administração') }}"
            class="w-full sm:min-w-[10rem] sm:flex-1"
            :empty-label="__('Administração')"
            :placeholder="__('Administração')"
            :search-placeholder="__('Buscar administração...')"
            :options="$administrations"
            :disabled="! $regionalId"
            :disabled-hint="$regionalId ? null : __('Escolha a regional antes.')"
        />
        <x-nexus.searchable-select
            wire:model.live="prayerHouseId"
            aria-label="{{ __('Casa de oração') }}"
            class="w-full sm:min-w-[10rem] sm:flex-1"
            :empty-label="__('Casa')"
            :placeholder="__('Casa')"
            :search-placeholder="__('Buscar casa...')"
            label-key="display_label"
            :options="$prayerHouses"
            :disabled="! $administrationId"
            :disabled-hint="$administrationId ? null : __('Escolha a administração antes da casa.')"
        />
        <x-ui.button type="button" wire:click="apply" size="sm" variant="secondary" class="min-h-11 w-full sm:w-auto" title="{{ __('O contexto já aplica ao mudar a regional; use se a página não atualizar.') }}">{{ __('Aplicar') }}</x-ui.button>
        @error('context')
            <x-nexus.inline-message class="w-full">{{ $message }}</x-nexus.inline-message>
        @enderror
    </div>
    </div>
@else
    <div class="flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-3 text-sm dark:border-slate-800 dark:bg-slate-950">
        <div class="min-w-[10rem] flex-1">
            <label for="context-regional" class="block text-xs font-semibold text-slate-700 dark:text-slate-200">{{ __('Regional ativa') }}</label>
            <x-nexus.searchable-select id="context-regional" wire:model.live="regionalId" class="mt-1" empty-label="—" search-placeholder="{{ __('Buscar regional...') }}" :options="$this->regionals" />
        </div>
        <div class="min-w-[10rem] flex-1">
            <label for="context-administration" class="block text-xs font-semibold text-slate-700 dark:text-slate-200">{{ __('Administração ativa') }}</label>
            <x-nexus.searchable-select id="context-administration" wire:model.live="administrationId" class="mt-1" empty-label="—" search-placeholder="{{ __('Buscar administração...') }}" :options="$this->administrations" :disabled="! $regionalId" :disabled-hint="$regionalId ? null : __('Escolha a regional antes.')" />
        </div>
        <div class="min-w-[10rem] flex-1">
            <label for="context-house" class="block text-xs font-semibold text-slate-700 dark:text-slate-200">{{ __('Casa de oração ativa') }}</label>
            <x-nexus.searchable-select id="context-house" wire:model.live="prayerHouseId" class="mt-1" empty-label="—" search-placeholder="{{ __('Buscar casa...') }}" label-key="display_label" :options="$this->prayerHouses" :disabled="! $administrationId" :disabled-hint="$administrationId ? null : __('Escolha a administração antes da casa.')" />
        </div>
        <x-ui.button type="button" wire:click="apply" size="sm" class="min-h-11 w-full sm:w-auto">{{ __('Aplicar contexto') }}</x-ui.button>
        @error('context')
            <x-nexus.inline-message class="w-full">{{ $message }}</x-nexus.inline-message>
        @enderror
    </div>
@endif
</div>
