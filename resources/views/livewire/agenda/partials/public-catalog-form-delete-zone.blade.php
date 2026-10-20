@props([
    'record' => null,
    'confirmMessage' => __('Excluir permanentemente? Esta ação não pode ser desfeita.'),
])

@if ($record)
    @include('livewire.agenda.partials.public-catalog-delete-error')

    @can('delete', $record)
        <div class="mt-8 rounded-xl border border-rose-200 bg-rose-50/60 p-4 dark:border-rose-900/60 dark:bg-rose-950/30">
            <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('Exclusão permanente') }}</h3>
            <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">
                {{ __('Desativar oculta o item nas listagens; excluir remove-o da base de dados.') }}
            </p>
            <button
                type="button"
                wire:click="destroyRecord"
                wire:confirm="{{ $confirmMessage }}"
                class="mt-3 rounded-lg border border-rose-300 bg-white px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50 dark:border-rose-800 dark:bg-slate-950 dark:text-rose-400 dark:hover:bg-rose-950/50"
            >{{ __('Excluir permanentemente') }}</button>
        </div>
    @endcan
@endif
