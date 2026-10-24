@php
    $start = ($align ?? 'end') === 'start';
    $justify = $start ? 'flex-col sm:flex-row sm:flex-wrap' : 'justify-end flex-nowrap';
    $item = $start ? 'w-full justify-center sm:w-auto' : '';
@endphp
<div class="flex gap-2 {{ $start ? 'items-stretch' : 'items-center' }} {{ $justify }}">
    @can('update', $row)
        <a href="{{ route('users.edit', $row) }}" wire:navigate class="inline-flex min-h-11 shrink-0 items-center gap-2 whitespace-nowrap rounded-xl border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-900 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100 dark:hover:bg-slate-900 {{ $item }}">
            <svg class="h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
            </svg>
            {{ __('Editar') }}
        </a>
    @endcan
    @can('sendPasswordReset', $row)
        <button type="button" wire:click="sendPasswordReset({{ $row->id }})" wire:confirm="Enviar o link de redefinição para o e-mail deste usuário? A senha não é mostrada." wire:loading.attr="disabled" wire:target="sendPasswordReset({{ $row->id }})" class="inline-flex min-h-11 shrink-0 items-center gap-2 whitespace-nowrap rounded-xl border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-900 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 disabled:opacity-50 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100 dark:hover:bg-slate-900 {{ $item }}">
            <svg class="h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
            </svg>
            Enviar link de senha
        </button>
    @endcan
    @can('impersonate', $row)
        <button type="button" wire:click="askImpersonation({{ $row->id }})" class="inline-flex min-h-11 shrink-0 items-center gap-2 whitespace-nowrap rounded-xl border border-amber-300 bg-amber-50 px-3 text-sm font-semibold text-amber-950 hover:bg-amber-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100 dark:hover:bg-amber-900/40 {{ $item }}">
            <svg class="h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
            </svg>
            Personificar
        </button>
    @endcan
</div>
