<x-ui.card title="{{ $this->meetingRoom ? 'Editar sala' : 'Nova sala' }}">
    <form wire:submit="save" class="grid max-w-xl gap-4">
        <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Dono</label>
            <select wire:model.live="owner_type" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950">
                <option value="{{ \App\Models\Administration::class }}">Administração</option>
                <option value="{{ \App\Models\PrayerHouse::class }}">Casa de oração</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Registo</label>
            <select wire:model="owner_id" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950">
                @if ($owner_type === \App\Models\PrayerHouse::class)
                    @foreach ($prayerHouses as $h)
                        <option value="{{ $h->id }}">{{ $h->name }}</option>
                    @endforeach
                @else
                    @foreach ($administrations as $a)
                        <option value="{{ $a->id }}">{{ $a->name }}</option>
                    @endforeach
                @endif
            </select>
            @error('owner_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
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
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Capacidade</label>
            <input type="number" wire:model="capacity" min="1" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Local físico (opcional)</label>
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
            <a href="{{ route('infrastructure.meeting-rooms.index') }}" wire:navigate class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold dark:border-slate-800">Cancelar</a>
        </div>
    </form>
</x-ui.card>
