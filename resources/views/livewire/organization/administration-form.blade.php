<x-ui.card title="{{ $this->administration ? 'Editar' : 'Nova' }} administração">
    <form wire:submit="save" class="grid max-w-xl gap-4">
        <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Regional</label>
            <select wire:model="regional_id" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950">
                @foreach ($regionals as $r)
                    <option value="{{ $r->id }}">{{ $r->name }}</option>
                @endforeach
            </select>
            @error('regional_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Nome</label>
            <input type="text" wire:model.live="name" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
            @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Slug</label>
            <input type="text" wire:model="slug" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
            @error('slug') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Local (opcional)</label>
            <select wire:model="location_id" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950">
                <option value="">—</option>
                @foreach ($locations as $loc)
                    <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                @endforeach
            </select>
        </div>
        <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" wire:model="is_active" class="rounded" /> Ativa</label>
        <div class="flex gap-2">
            <x-ui.button type="submit">Guardar</x-ui.button>
            <a href="{{ route('organization.administrations.index') }}" wire:navigate class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold dark:border-slate-800">Cancelar</a>
        </div>
    </form>
</x-ui.card>
