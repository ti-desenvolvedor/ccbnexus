<div
    x-data
    x-cloak
    x-show="$store.nexusDialog.open"
    x-transition.opacity.duration.200ms
    x-init="$watch('$store.nexusDialog.open', (open) => {
        document.body.classList.toggle('overflow-y-hidden', open);
        if (!open) {
            return;
        }
        $nextTick(() => {
            const dialog = $store.nexusDialog;
            const focusCancel = dialog.mode === 'confirm' && dialog.tone === 'danger';
            (focusCancel ? $refs.cancel : $refs.accept)?.focus();
        });
    })"
    x-on:keydown.escape.window.stop.prevent="if ($store.nexusDialog.open) $store.nexusDialog.dismiss()"
    class="fixed inset-0 z-[100] flex items-end justify-center px-4 py-6 sm:items-center"
    role="alertdialog"
    aria-modal="true"
    aria-labelledby="nexus-dialog-title"
    :aria-describedby="$store.nexusDialog.message ? 'nexus-dialog-message' : null"
>
    <div class="absolute inset-0 bg-slate-950/70" x-on:click="$store.nexusDialog.dismiss()"></div>
    <div class="relative w-full max-w-md overflow-hidden rounded-lg bg-white text-slate-900 shadow-xl dark:bg-slate-900 dark:text-slate-100">
        <header
            class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-4 dark:border-slate-800"
            :class="{
                'bg-rose-50 dark:bg-rose-950/40': $store.nexusDialog.tone === 'danger',
                'bg-amber-50 dark:bg-amber-950/40': $store.nexusDialog.tone === 'warning',
                'bg-emerald-50 dark:bg-emerald-950/40': $store.nexusDialog.tone === 'success',
                'bg-white dark:bg-slate-950': !['danger', 'warning', 'success'].includes($store.nexusDialog.tone),
            }"
        >
            <x-nexus.dialog-icon size="sm" x-show="!$store.nexusDialog.message" />
            <h2 id="nexus-dialog-title" class="min-w-0 flex-1 text-base font-semibold text-slate-900 dark:text-slate-100" x-text="$store.nexusDialog.title"></h2>
            <button type="button" x-on:click="$store.nexusDialog.dismiss()" aria-label="Fechar" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-slate-600 hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:text-slate-300 dark:hover:bg-slate-800">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                    <path stroke-width="2" stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                </svg>
            </button>
        </header>
        <div class="flex items-center gap-4 px-4 py-6" x-show="$store.nexusDialog.message">
            <x-nexus.dialog-icon size="lg" />
            <p id="nexus-dialog-message" class="min-w-0 whitespace-pre-wrap text-sm leading-relaxed text-slate-700 dark:text-slate-300" x-text="$store.nexusDialog.message"></p>
        </div>
        <footer class="flex flex-col gap-2 border-t border-slate-200 bg-slate-50 px-4 py-4 sm:flex-row sm:justify-end dark:border-slate-800 dark:bg-slate-950">
            <button
                type="button"
                x-ref="cancel"
                x-show="$store.nexusDialog.mode === 'confirm'"
                x-on:click="$store.nexusDialog.dismiss()"
                class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
            ><span x-text="$store.nexusDialog.cancelLabel"></span></button>
            <button
                type="button"
                x-ref="accept"
                x-on:click="$store.nexusDialog.accept()"
                class="inline-flex min-h-11 items-center justify-center rounded-xl px-4 text-sm font-semibold text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                :class="$store.nexusDialog.tone === 'danger' ? 'bg-rose-600 hover:bg-rose-700' : 'bg-primary-600 hover:bg-primary-700'"
                x-text="$store.nexusDialog.confirmLabel"
            ></button>
        </footer>
    </div>
</div>
