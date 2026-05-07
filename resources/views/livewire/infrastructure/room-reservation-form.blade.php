<x-ui.card title="Nova reserva">
    @if ($meetingRooms->isEmpty())
        <p class="text-sm text-slate-600 dark:text-slate-300">Não há salas disponíveis no seu âmbito. Crie uma sala em Operação → Salas.</p>
    @else
    <form wire:submit="save" class="grid max-w-xl gap-4">
        <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Sala</label>
            <select wire:model="meeting_room_id" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950">
                @foreach ($meetingRooms as $r)
                    <option value="{{ $r->id }}">{{ $r->name }}</option>
                @endforeach
            </select>
            @error('meeting_room_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Título</label>
            <input type="text" wire:model="title" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
            @error('title') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Notas</label>
            <textarea wire:model="notes" rows="3" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950"></textarea>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Início</label>
                <input type="datetime-local" wire:model="starts_at" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
                @error('starts_at') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Fim</label>
                <input type="datetime-local" wire:model="ends_at" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
                @error('ends_at') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
        </div>
        <label class="inline-flex items-center gap-2 text-sm">
            <input type="checkbox" wire:model="requires_approval" class="rounded" />
            Exige aprovação
        </label>
        <div class="flex gap-2">
            <x-ui.button type="submit">Guardar</x-ui.button>
            <a href="{{ route('infrastructure.room-reservations.index') }}" wire:navigate class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold dark:border-slate-800">Cancelar</a>
        </div>
    </form>
    @endif
</x-ui.card>
