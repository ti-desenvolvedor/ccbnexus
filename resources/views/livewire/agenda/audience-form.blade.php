<x-ui.card title="{{ $this->audience ? 'Editar público' : 'Novo público' }}">
    <form wire:submit="save" class="grid w-full min-w-0 gap-4 md:grid-cols-2">
        <div>
            <label class="text-xs font-semibold text-slate-500">{{ __('Nome') }}</label>
            <input type="text" wire:model.live="name" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
            @error('name') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-500">{{ __('Ordem') }}</label>
            <input type="number" wire:model="sort_order" min="0" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
        </div>
        <label class="flex items-center gap-2 text-sm md:col-span-2"><input type="checkbox" wire:model="is_active" class="rounded" /> {{ __('Ativo') }}</label>
        <p class="text-xs text-slate-500 md:col-span-2">{{ __('Desativar oculta nas listagens; para remover da base use a exclusão permanente abaixo.') }}</p>
        <div class="flex flex-wrap gap-2 md:col-span-2">
            <x-ui.button type="submit">{{ __('Guardar') }}</x-ui.button>
            <a href="{{ route('agenda.audiences.index') }}" wire:navigate class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold dark:border-slate-800">{{ __('Cancelar') }}</a>
        </div>
    </form>

    @include('livewire.agenda.partials.public-catalog-form-delete-zone', [
        'record' => $this->audience,
        'confirmMessage' => __('Excluir esta audiência permanentemente?'),
    ])
</x-ui.card>
