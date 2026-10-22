@php
    $closeIcon = '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-width="2" stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>';
    $refreshIcon = '<svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M20 12a8 8 0 00-13.7-5.6L4 8"/><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M4 4v4h4"/><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M4 12a8 8 0 0013.7 5.6L20 16"/><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M20 20v-4h-4"/></svg>';
    $revisionIcon = '<svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M7 3h8l5 5v13H7z"/><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M15 3v5h5"/><path stroke-width="2" stroke-linecap="round" d="M12 12v5M9.5 14.5h5"/></svg>';
    $nextRevision = sprintf('%02d', ((int) ($report->revision ?? 0)) + 1);
@endphp

<x-modal name="baptism-new-revision" focusable maxWidth="md">
    <div role="dialog" aria-modal="true" aria-labelledby="baptism-new-revision-title" class="bg-white dark:bg-slate-950">
        <header class="flex items-center justify-between gap-3 border-b border-slate-200 bg-emerald-50 px-4 py-4 dark:border-slate-800 dark:bg-emerald-950/40">
            <h2 id="baptism-new-revision-title" class="text-base font-semibold text-slate-900 dark:text-slate-100">Criar a revisão R{{ $nextRevision }}?</h2>
            <button type="button" x-on:click="$dispatch('close')" aria-label="Fechar" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-slate-600 hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:text-slate-300 dark:hover:bg-slate-900">{!! $closeIcon !!}</button>
        </header>
        <div class="flex items-center gap-4 px-4 py-6">
            <span class="inline-flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-100">{!! $revisionIcon !!}</span>
            <p class="min-w-0 text-sm leading-relaxed text-slate-600 dark:text-slate-300">A revisão atual permanece. A nova começa em rascunho, sem as verificações desta.</p>
        </div>
        <footer class="flex flex-col gap-2 border-t border-slate-200 bg-slate-50 px-4 py-4 sm:flex-row sm:justify-end dark:border-slate-800 dark:bg-slate-900">
            <button type="button" x-on:click="$dispatch('close')" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100">Cancelar</button>
            <button type="button" wire:click="generateNewRevision" x-on:click="$dispatch('close')" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-primary-600 px-4 text-sm font-semibold text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">Criar revisão</button>
        </footer>
    </div>
</x-modal>

<x-modal name="baptism-regenerate" focusable maxWidth="md">
    <div role="dialog" aria-modal="true" aria-labelledby="baptism-regenerate-title" class="bg-white dark:bg-slate-950">
        <header class="flex items-center justify-between gap-3 border-b border-slate-200 bg-rose-50 px-4 py-4 dark:border-slate-800 dark:bg-rose-950/40">
            <h2 id="baptism-regenerate-title" class="text-base font-semibold text-slate-900 dark:text-slate-100">Atualizar esta revisão?</h2>
            <button type="button" x-on:click="$dispatch('close')" aria-label="Fechar" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-slate-600 hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:text-slate-300 dark:hover:bg-slate-900">{!! $closeIcon !!}</button>
        </header>
        <div class="flex items-center gap-4 px-4 py-6">
            <span class="inline-flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-rose-50 text-rose-800 dark:bg-rose-950/40 dark:text-rose-200">{!! $refreshIcon !!}</span>
            <p class="min-w-0 text-sm leading-relaxed text-slate-600 dark:text-slate-300">A lista é gerada de novo. As verificações e a aprovação desta revisão são apagadas.</p>
        </div>
        <footer class="flex flex-col gap-2 border-t border-slate-200 bg-slate-50 px-4 py-4 sm:flex-row sm:justify-end dark:border-slate-800 dark:bg-slate-900">
            <button type="button" x-on:click="$dispatch('close')" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100">Cancelar</button>
            <button type="button" wire:click="generate" x-on:click="$dispatch('close')" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-rose-600 px-4 text-sm font-semibold text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">Atualizar</button>
        </footer>
    </div>
</x-modal>

<x-modal name="baptism-reset-periods" focusable maxWidth="md">
    <div role="dialog" aria-modal="true" aria-labelledby="baptism-reset-periods-title" class="bg-white dark:bg-slate-950">
        <header class="flex items-center justify-between gap-3 border-b border-slate-200 bg-rose-50 px-4 py-4 dark:border-slate-800 dark:bg-rose-950/40">
            <h2 id="baptism-reset-periods-title" class="text-base font-semibold text-slate-900 dark:text-slate-100">Restaurar os períodos?</h2>
            <button type="button" x-on:click="$dispatch('close')" aria-label="Fechar" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-slate-600 hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:text-slate-300 dark:hover:bg-slate-900">{!! $closeIcon !!}</button>
        </header>
        <div class="flex items-center gap-4 px-4 py-6">
            <span class="inline-flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-rose-50 text-rose-800 dark:bg-rose-950/40 dark:text-rose-200">{!! $refreshIcon !!}</span>
            <p class="min-w-0 text-sm leading-relaxed text-slate-600 dark:text-slate-300">As janelas voltam ao padrão e a lista é gerada de novo. As verificações e a aprovação desta revisão são apagadas.</p>
        </div>
        <footer class="flex flex-col gap-2 border-t border-slate-200 bg-slate-50 px-4 py-4 sm:flex-row sm:justify-end dark:border-slate-800 dark:bg-slate-900">
            <button type="button" x-on:click="$dispatch('close')" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100">Cancelar</button>
            <button type="button" wire:click="resetSectionFilters" x-on:click="$dispatch('close')" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-rose-600 px-4 text-sm font-semibold text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">Restaurar</button>
        </footer>
    </div>
</x-modal>

<x-modal name="baptism-apply-period" focusable maxWidth="md">
    <div role="dialog" aria-modal="true" aria-labelledby="baptism-apply-period-title" class="bg-white dark:bg-slate-950">
        <header class="flex items-center justify-between gap-3 border-b border-slate-200 bg-rose-50 px-4 py-4 dark:border-slate-800 dark:bg-rose-950/40">
            <h2 id="baptism-apply-period-title" class="text-base font-semibold text-slate-900 dark:text-slate-100">Aplicar este período?</h2>
            <button type="button" x-on:click="$dispatch('close')" aria-label="Fechar" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-slate-600 hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:text-slate-300 dark:hover:bg-slate-900">{!! $closeIcon !!}</button>
        </header>
        <div class="flex items-center gap-4 px-4 py-6">
            <span class="inline-flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-rose-50 text-rose-800 dark:bg-rose-950/40 dark:text-rose-200">{!! $refreshIcon !!}</span>
            <p class="min-w-0 text-sm leading-relaxed text-slate-600 dark:text-slate-300">A lista é gerada de novo com estas datas. As verificações e a aprovação desta revisão são apagadas.</p>
        </div>
        <footer class="flex flex-col gap-2 border-t border-slate-200 bg-slate-50 px-4 py-4 sm:flex-row sm:justify-end dark:border-slate-800 dark:bg-slate-900">
            <button type="button" x-on:click="$dispatch('close')" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100">Cancelar</button>
            <button type="button" wire:click="confirmApplySection" x-on:click="$dispatch('close')" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-rose-600 px-4 text-sm font-semibold text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">Aplicar</button>
        </footer>
    </div>
</x-modal>
