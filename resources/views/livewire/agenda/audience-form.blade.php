<x-ui.card title="{{ $this->audience ? 'Editar público' : 'Novo público' }}">
    <form wire:submit="save" class="grid max-w-md gap-4">
        <div>
            <label class="text-xs font-semibold text-slate-500">Nome</label>
            <input type="text" wire:model.live="name" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
            @error('name') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-500">Slug</label>
            <input type="text" wire:model="slug" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
            @error('slug') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-500">Ordem</label>
            <input type="number" wire:model="sort_order" min="0" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
        </div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="is_active" class="rounded" /> Ativo</label>
        <div class="flex gap-2">
            <x-ui.button type="submit">Guardar</x-ui.button>
            <a href="{{ route('agenda.audiences.index') }}" wire:navigate class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold dark:border-slate-800">Cancelar</a>
        </div>
    </form>
</x-ui.card>
