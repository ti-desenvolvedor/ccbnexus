<div class="hidden border-t px-3 py-3 lg:block" style="border-color: rgb(var(--sidebar-border) / 1);">
    <button
        type="button"
        class="sidebar-root flex min-h-11 w-full items-center gap-3 rounded-xl bg-white px-3 py-2 text-left text-sm font-semibold text-[rgb(var(--sidebar-text)/1)] shadow-sm ring-1 ring-slate-200/70 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:bg-slate-900/50 dark:ring-slate-800/70 dark:hover:bg-white/10"
        x-bind:aria-pressed="$store.nexus.sidebarDensity === 'icons'"
        x-bind:aria-label="$store.nexus.sidebarDensity === 'icons' ? @js(__('Usar menu normal')) : @js(__('Minimizar menu'))"
        x-on:click="
            const next = $store.nexus.sidebarDensity === 'icons' ? 'expanded' : 'icons';
            $store.nexus.applySidebarDensity(next);
            $wire.setDensity(next);
        "
    >
        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-slate-50 ring-1 ring-slate-200/60 dark:bg-white/10 dark:ring-0">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                <rect x="3" y="4" width="18" height="16" rx="2" stroke-width="2"></rect>
                <path stroke-width="2" stroke-linecap="round" d="M9 4v16"></path>
            </svg>
        </span>
        <span class="sidebar-copy min-w-0 flex-1 truncate" x-text="$store.nexus.sidebarDensity === 'icons' ? @js(__('Menu normal')) : @js(__('Minimizar menu'))"></span>
    </button>
</div>
