<x-ui.card title="{{ $this->location ? 'Editar local' : 'Novo local' }}">
    <form wire:submit="save" class="grid max-w-2xl gap-4">
        <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Nome do local</label>
            <input type="text" wire:model="name" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" placeholder="Ex.: Sede, Casa de oração Centro" />
            @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">País (ISO-2)</label>
            <input type="text" wire:model.live="country" maxlength="2" class="mt-1 w-full max-w-[6rem] uppercase rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
            @error('country') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
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
                        @error('postal_code') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
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
                    <select wire:model.live="state" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950">
                        <option value="">— Selecione —</option>
                        @foreach ($brazilianStates as $sigla => $nomeUf)
                            <option value="{{ $sigla }}">{{ $sigla }} — {{ $nomeUf }}</option>
                        @endforeach
                    </select>
                    @error('state') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Município</label>
                    <select
                        wire:model="city"
                        @disabled($state === '' || $loadingCities || count($cityOptions) === 0)
                        class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-60 dark:border-slate-800 dark:bg-slate-950"
                    >
                        <option value="">
                            @if ($state === '')
                                Selecione primeiro a UF
                            @elseif ($loadingCities)
                                A carregar municípios…
                            @elseif (count($cityOptions) === 0)
                                Sem dados IBGE para esta UF
                            @else
                                — Selecione o município —
                            @endif
                        </option>
                        @foreach ($cityOptions as $nomeMunicipio)
                            <option value="{{ $nomeMunicipio }}">{{ $nomeMunicipio }}</option>
                        @endforeach
                    </select>
                    @error('city') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Bairro</label>
                <input type="text" wire:model="district" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
                @error('district') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-6">
                <div class="sm:col-span-4">
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Logradouro</label>
                    <input type="text" wire:model="line1" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" placeholder="Rua, avenida, etc." />
                    @error('line1') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Número</label>
                    <input type="text" wire:model="number" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" placeholder="S/N" />
                    @error('number') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Complemento</label>
                <input type="text" wire:model="complement" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" placeholder="Bloco, sala, andar…" />
                @error('complement') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
        @else
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Endereço</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Cidade</label>
                    <input type="text" wire:model="city" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
                    @error('city') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Estado / província</label>
                    <input type="text" wire:model="state" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
                    @error('state') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">CEP / código postal</label>
                <input type="text" wire:model="postal_code" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
                @error('postal_code') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div class="grid gap-4 sm:grid-cols-6">
                <div class="sm:col-span-4">
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Linha de endereço</label>
                    <input type="text" wire:model="line1" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
                    @error('line1') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Número</label>
                    <input type="text" wire:model="number" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
                    @error('number') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Complemento</label>
                <input type="text" wire:model="complement" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
                @error('complement') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Bairro / distrito</label>
                <input type="text" wire:model="district" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
                @error('district') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
        @endif

        <div class="flex gap-2">
            <x-ui.button type="submit">Guardar</x-ui.button>
            <a href="{{ route('infrastructure.locations.index') }}" wire:navigate class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold dark:border-slate-800">Cancelar</a>
        </div>
    </form>
</x-ui.card>
