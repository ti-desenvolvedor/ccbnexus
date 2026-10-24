<x-ui.card title="{{ $this->location ? 'Editar local' : 'Novo local' }}">
    <form wire:submit="save" data-progress="marked" class="flex w-full min-w-0 flex-col gap-4">
        <x-nexus.form-progress />
        <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Nome do local</label>
            <input type="text" wire:model="name" data-progress-field class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" placeholder="Ex.: Sede, Casa de oração Centro" />
            @error('name') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/40">
            <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Âmbito organizacional</p>
            <label class="mb-3 inline-flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model.live="is_regional_hq" class="rounded" />
                Sede regional (sem administração)
            </label>
            @if ($is_regional_hq)
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('Regional') }}</label>
                    <x-nexus.searchable-select wire:model="regional_id" data-progress-field class="mt-1" empty-label="—" search-placeholder="{{ __('Buscar regional...') }}" :options="$regionals" />
                    @error('regional_id') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
                </div>
            @else
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('Administração') }}</label>
                    <x-nexus.searchable-select
                        wire:model="administration_id"
                        data-progress-field
                        class="mt-1"
                        empty-label="—"
                        search-placeholder="{{ __('Buscar administração...') }}"
                        :options="$administrations->map(fn ($a) => ['value' => (string) $a->id, 'label' => $a->name.' — '.($a->regional?->name ?? '')])"
                    />
                    @error('administration_id') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
                </div>
            @endif
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">País (ISO-2)</label>
            <input type="text" wire:model.live="country" data-progress-field maxlength="2" class="mt-1 w-full max-w-[6rem] uppercase rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
            @error('country') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
            @if (strtoupper($country) !== 'BR')
                <p class="mt-1 text-xs text-slate-500">Para país diferente de BR, cidade e UF são texto livre abaixo.</p>
            @endif
        </div>

        @if (strtoupper($country) === 'BR')
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Endereço (Brasil)</p>

            <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/40">
                <p class="mb-2 text-xs font-semibold text-slate-600 dark:text-slate-400">Buscar por CEP</p>
                <div class="flex flex-wrap items-end gap-2">
                    <div class="min-w-[10rem] flex-1">
                        <label class="block text-xs text-slate-600 dark:text-slate-400">CEP</label>
                        <input type="text" wire:model="postal_code" inputmode="numeric" autocomplete="postal-code" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" placeholder="00000-000" />
                        @error('postal_code') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
                    </div>
                    <x-ui.button type="button" wire:click="fetchCep" wire:loading.attr="disabled" wire:target="fetchCep" class="shrink-0">
                        <span wire:loading.remove wire:target="fetchCep">Buscar CEP</span>
                        <span wire:loading wire:target="fetchCep">A buscar…</span>
                    </x-ui.button>
                </div>
                @if ($address_feedback)
                    <p class="mt-2 text-xs text-slate-600 dark:text-slate-300">{{ $address_feedback }}</p>
                @endif
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">UF</label>
                    <x-nexus.searchable-select
                        wire:model.live="state"
                        class="mt-1"
                        empty-label="— Selecione —"
                        search-placeholder="Buscar UF..."
                        :options="collect($brazilianStates)->map(fn ($nomeUf, $sigla) => ['value' => $sigla, 'label' => $sigla.' — '.$nomeUf])"
                    />
                    @error('state') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Município</label>
                    <x-nexus.searchable-select
                        wire:model="city"
                        class="mt-1"
                        search-placeholder="Buscar município..."
                        :disabled="$state === '' || $loadingCities || count($cityOptions) === 0"
                        :empty-label="$state === '' ? 'Selecione primeiro a UF' : ($loadingCities ? 'A carregar municípios…' : (count($cityOptions) === 0 ? 'Sem dados IBGE para esta UF' : '— Selecione o município —'))"
                        :options="collect($cityOptions)->map(fn ($nomeMunicipio) => ['value' => $nomeMunicipio, 'label' => $nomeMunicipio])"
                    />
                    @error('city') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Bairro</label>
                <input type="text" wire:model="district" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
                @error('district') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-6">
                <div class="sm:col-span-4">
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Logradouro</label>
                    <input type="text" wire:model="line1" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" placeholder="Rua, avenida, etc." />
                    @error('line1') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Número</label>
                    <input type="text" wire:model="number" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" placeholder="S/N" />
                    @error('number') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Complemento</label>
                <input type="text" wire:model="complement" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" placeholder="Bloco, sala, andar…" />
                @error('complement') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
            </div>
        @else
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Endereço</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Cidade</label>
                    <input type="text" wire:model="city" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
                    @error('city') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Estado / província</label>
                    <input type="text" wire:model="state" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
                    @error('state') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">CEP / código postal</label>
                <input type="text" wire:model="postal_code" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
                @error('postal_code') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
            </div>
            <div class="grid gap-4 sm:grid-cols-6">
                <div class="sm:col-span-4">
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Linha de endereço</label>
                    <input type="text" wire:model="line1" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
                    @error('line1') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Número</label>
                    <input type="text" wire:model="number" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
                    @error('number') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Complemento</label>
                <input type="text" wire:model="complement" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
                @error('complement') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Bairro / distrito</label>
                <input type="text" wire:model="district" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
                @error('district') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
            </div>
        @endif

        <div class="flex flex-wrap gap-2">
            <x-ui.button type="submit">{{ __('Guardar') }}</x-ui.button>
            <a href="{{ route('infrastructure.locations.index') }}" wire:navigate class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold dark:border-slate-800">{{ __('Cancelar') }}</a>
        </div>
    </form>
</x-ui.card>
