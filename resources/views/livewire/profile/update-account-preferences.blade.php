<section
    x-data
    x-on:account-preferences-updated.window="
        if ($event.detail.theme) {
            $store.nexus.setTheme($event.detail.theme);
        }
        if ($event.detail.palette) {
            $store.nexus.setPalette($event.detail.palette);
        }
    "
>
    <x-profile.section
        :title="__('Preferências da conta')"
        :description="__('Paginação das listagens e aparência do painel — guardadas na sua conta.')"
    >
        <x-slot name="icon">
            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m0 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3.75 0H7.5m3-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
            </svg>
        </x-slot>

        @if (session('preferences_status'))
            <p class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300" role="status">
                {{ session('preferences_status') }}
            </p>
        @endif

        <form wire:submit="save" class="space-y-8">
            <fieldset class="space-y-4">
                <legend class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('Itens por página nas listagens') }}</legend>
                <p class="text-sm text-slate-600 dark:text-slate-400">
                    {{ __('Aplica-se a eventos, utilizadores, casas de oração, catálogo público e demais tabelas paginadas.') }}
                </p>

                <div class="flex flex-wrap gap-2">
                    @foreach ($this->perPageOptions as $option)
                        <button
                            type="button"
                            wire:click="setListPerPage({{ $option }})"
                            class="min-w-[3rem] rounded-xl border px-4 py-2.5 text-sm font-semibold transition
                                {{ $list_per_page === $option
                                    ? 'border-primary-600 bg-primary-600 text-white shadow-sm'
                                    : 'border-slate-200 bg-white text-slate-700 hover:border-primary-300 hover:bg-primary-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-primary-700 dark:hover:bg-primary-950/40' }}"
                        >
                            {{ $option }}
                        </button>
                    @endforeach
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/40">
                    <label for="list_per_page_custom" class="block text-sm font-medium text-slate-900 dark:text-slate-100">
                        {{ __('Valor personalizado') }}
                    </label>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                        {{ __('Entre :min e :max itens por página.', ['min' => $this->perPageMin, 'max' => $this->perPageMax]) }}
                    </p>
                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <input
                            wire:model="list_per_page"
                            id="list_per_page_custom"
                            type="number"
                            min="{{ $this->perPageMin }}"
                            max="{{ $this->perPageMax }}"
                            step="1"
                            class="w-28 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                        />
                        <span class="text-sm text-slate-600 dark:text-slate-400">{{ __('itens por página') }}</span>
                        @if (! $this->isPresetPerPage($list_per_page))
                            <span class="inline-flex items-center rounded-full bg-primary-100 px-2.5 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-950/60 dark:text-primary-300">
                                {{ __('Personalizado') }}
                            </span>
                        @endif
                    </div>
                    <x-input-error class="mt-2" :messages="$errors->get('list_per_page')" />
                </div>
            </fieldset>

            <fieldset class="space-y-4 border-t border-slate-200 pt-6 dark:border-slate-800">
                <legend class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('Aparência') }}</legend>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="ui_theme" :value="__('Tema padrão')" />
                        <x-nexus.searchable-select
                            id="ui_theme"
                            wire:model.live="ui_theme"
                            class="mt-1"
                            :search-placeholder="__('Buscar tema...')"
                            x-on:nexus-select="$store.nexus.setTheme($event.detail)"
                            :options="collect($this->themeOptions)->map(fn ($theme) => ['value' => $theme, 'label' => $theme === 'dark' ? __('Escuro') : __('Claro')])"
                        />
                        <x-input-error class="mt-2" :messages="$errors->get('ui_theme')" />
                    </div>

                    <div>
                        <x-input-label for="ui_palette" :value="__('Cor primária padrão')" />
                        <x-nexus.searchable-select
                            id="ui_palette"
                            wire:model.live="ui_palette"
                            class="mt-1"
                            :search-placeholder="__('Buscar cor...')"
                            x-on:nexus-select="$store.nexus.setPalette($event.detail)"
                            :options="collect($this->paletteOptions)->map(fn ($palette) => ['value' => $palette, 'label' => match ($palette) {
                                'blue' => __('Azul'),
                                'navy' => __('Azul marinho'),
                                'green' => __('Verde'),
                                'green_dark' => __('Verde escuro'),
                                'red' => __('Vermelho'),
                                'red_dark' => __('Vermelho escuro'),
                                'orange' => __('Laranja'),
                                'brown' => __('Marrom'),
                                default => $palette,
                            }])"
                        />
                        <x-input-error class="mt-2" :messages="$errors->get('ui_palette')" />
                    </div>
                </div>
            </fieldset>

            <div class="flex items-center gap-4 border-t border-slate-200 pt-4 dark:border-slate-800">
                <x-primary-button>{{ __('Guardar preferências') }}</x-primary-button>
            </div>
        </form>
    </x-profile.section>
</section>
