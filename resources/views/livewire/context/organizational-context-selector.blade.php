<div class="flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-3 text-sm dark:border-slate-800 dark:bg-slate-950">
    <div class="min-w-[10rem] flex-1">
        <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400">Regional ativa</label>
        <select wire:model.live="regionalId" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 dark:border-slate-700 dark:bg-slate-900">
            <option value="">—</option>
            @foreach ($this->regionals as $r)
                <option value="{{ $r->id }}">{{ $r->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="min-w-[10rem] flex-1">
        <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400">Administração ativa</label>
        <select wire:model.live="administrationId" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 dark:border-slate-700 dark:bg-slate-900">
            <option value="">—</option>
            @foreach ($this->administrations as $a)
                <option value="{{ $a->id }}">{{ $a->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="min-w-[10rem] flex-1">
        <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400">Casa de oração ativa</label>
        <select wire:model.live="prayerHouseId" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 dark:border-slate-700 dark:bg-slate-900">
            <option value="">—</option>
            @foreach ($this->prayerHouses as $h)
                <option value="{{ $h->id }}">{{ $h->name }}</option>
            @endforeach
        </select>
    </div>
    <x-ui.button type="button" wire:click="apply" size="sm">Aplicar contexto</x-ui.button>
    @error('context')
        <p class="w-full text-xs text-rose-600">{{ $message }}</p>
    @enderror
</div>
