<x-ui.card title="{{ $this->department ? __('Editar departamento') : __('Novo departamento') }}">
    <form wire:submit="save" class="grid max-w-lg gap-4">
        <div>
            <label class="text-xs font-semibold text-slate-500">{{ __('Âmbito') }}</label>
            <select wire:model.live="scope" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950">
                <option value="{{ \App\Models\PublicDepartment::SCOPE_REGIONAL }}">{{ __('Regional') }}</option>
                <option value="{{ \App\Models\PublicDepartment::SCOPE_ADMINISTRATION }}">{{ __('Administração') }}</option>
                <option value="{{ \App\Models\PublicDepartment::SCOPE_PRAYER_HOUSE }}">{{ __('Casa de oração') }}</option>
            </select>
        </div>
        @if ($scope === \App\Models\PublicDepartment::SCOPE_REGIONAL)
            <div>
                <label class="text-xs font-semibold text-slate-500">{{ __('Regional') }}</label>
                <select wire:model="regional_id" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950">
                    @foreach ($regionals as $r)
                        <option value="{{ $r->id }}">{{ $r->name }}</option>
                    @endforeach
                </select>
                @error('regional_id') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
        @endif
        @if ($scope === \App\Models\PublicDepartment::SCOPE_ADMINISTRATION)
            <div>
                <label class="text-xs font-semibold text-slate-500">{{ __('Administração') }}</label>
                <select wire:model="administration_id" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950">
                    <option value="">—</option>
                    @foreach ($administrations as $a)
                        <option value="{{ $a->id }}">{{ $a->name }} ({{ $a->regional?->name }})</option>
                    @endforeach
                </select>
                @error('administration_id') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
        @endif
        @if ($scope === \App\Models\PublicDepartment::SCOPE_PRAYER_HOUSE)
            <div>
                <label class="text-xs font-semibold text-slate-500">{{ __('Casa de oração') }}</label>
                <select wire:model="prayer_house_id" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950">
                    <option value="">—</option>
                    @foreach ($prayerHouses as $h)
                        <option value="{{ $h->id }}">{{ $h->name }}</option>
                    @endforeach
                </select>
                @error('prayer_house_id') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
        @endif
        <div>
            <label class="text-xs font-semibold text-slate-500">{{ __('Nome') }}</label>
            <input type="text" wire:model.live="name" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
            @error('name') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-500">Slug</label>
            <input type="text" wire:model="slug" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
            @error('slug') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-500">{{ __('Ordem') }}</label>
            <input type="number" wire:model="sort_order" min="0" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
        </div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="is_active" class="rounded" /> {{ __('Ativo') }}</label>
        <div class="flex gap-2">
            <x-ui.button type="submit">{{ __('Guardar') }}</x-ui.button>
            <a href="{{ route('agenda.public-departments.index') }}" wire:navigate class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold dark:border-slate-800">{{ __('Cancelar') }}</a>
        </div>
    </form>
</x-ui.card>
