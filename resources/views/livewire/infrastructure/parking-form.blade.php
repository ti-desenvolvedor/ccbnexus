<x-ui.card title="{{ $this->parking ? 'Editar estacionamento' : 'Novo estacionamento' }}">
    <form wire:submit="save" class="grid max-w-md gap-4">
        <div>
            <label class="text-xs font-semibold text-slate-500">Local</label>
            <select wire:model="location_id" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950">
                @foreach ($locations as $l)
                    <option value="{{ $l->id }}">{{ $l->name }}</option>
                @endforeach
            </select>
            @error('location_id') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-500">Nome</label>
            <input type="text" wire:model="name" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
            @error('name') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-500">Capacidade</label>
            <input type="number" wire:model="capacity" min="1" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
        </div>
        <div class="flex gap-2">
            <x-ui.button type="submit">Guardar</x-ui.button>
            <a href="{{ route('infrastructure.parkings.index') }}" wire:navigate class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold dark:border-slate-800">Cancelar</a>
        </div>
    </form>
</x-ui.card>
