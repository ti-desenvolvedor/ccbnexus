<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3" wire:init="refresh">
    <x-ui.card>
        <div class="text-xs font-semibold text-slate-500 dark:text-slate-400">Regionais ativas</div>
        <div class="mt-2">
            <div wire:loading.block wire:target="refresh">
                <x-ui.skeleton class="h-9 w-16" />
            </div>
            <div wire:loading.remove wire:target="refresh" class="text-3xl font-bold tracking-tight tabular-nums">
                {{ number_format($regionals, 0, ',', '.') }}
            </div>
        </div>
        <p class="mt-2 text-xs text-slate-600 dark:text-slate-400">No seu escopo de acesso.</p>
    </x-ui.card>

    <x-ui.card>
        <div class="text-xs font-semibold text-slate-500 dark:text-slate-400">Administrações</div>
        <div class="mt-2">
            <div wire:loading.block wire:target="refresh">
                <x-ui.skeleton class="h-9 w-16" />
            </div>
            <div wire:loading.remove wire:target="refresh" class="text-3xl font-bold tracking-tight tabular-nums">
                {{ number_format($administrations, 0, ',', '.') }}
            </div>
        </div>
        <p class="mt-2 text-xs text-slate-600 dark:text-slate-400">Subdivisões dentro das regionais.</p>
    </x-ui.card>

    <x-ui.card>
        <div class="text-xs font-semibold text-slate-500 dark:text-slate-400">Casas de oração</div>
        <div class="mt-2">
            <div wire:loading.block wire:target="refresh">
                <x-ui.skeleton class="h-9 w-16" />
            </div>
            <div wire:loading.remove wire:target="refresh" class="text-3xl font-bold tracking-tight tabular-nums">
                {{ number_format($prayer_houses, 0, ',', '.') }}
            </div>
        </div>
        <p class="mt-2 text-xs text-slate-600 dark:text-slate-400">Vinculadas às administrações do escopo.</p>
    </x-ui.card>

    <x-ui.card>
        <div class="text-xs font-semibold text-slate-500 dark:text-slate-400">Eventos (próximos 30 dias)</div>
        <div class="mt-2">
            <div wire:loading.block wire:target="refresh">
                <x-ui.skeleton class="h-9 w-16" />
            </div>
            <div wire:loading.remove wire:target="refresh" class="text-3xl font-bold tracking-tight tabular-nums">
                {{ number_format($upcoming_events, 0, ',', '.') }}
            </div>
        </div>
        <p class="mt-2 text-xs text-slate-600 dark:text-slate-400">Séries principais, exceto cancelados.</p>
    </x-ui.card>

    <x-ui.card>
        <div class="text-xs font-semibold text-slate-500 dark:text-slate-400">Reservas pendentes</div>
        <div class="mt-2">
            <div wire:loading.block wire:target="refresh">
                <x-ui.skeleton class="h-9 w-16" />
            </div>
            <div wire:loading.remove wire:target="refresh" class="text-3xl font-bold tracking-tight tabular-nums">
                {{ number_format($pending_reservations, 0, ',', '.') }}
            </div>
        </div>
        <p class="mt-2 text-xs text-slate-600 dark:text-slate-400">Salas no seu escopo, aguardando decisão.</p>
    </x-ui.card>

    @if ($pending_access_requests !== null)
        <x-ui.card>
            <div class="text-xs font-semibold text-slate-500 dark:text-slate-400">Pedidos de acesso</div>
            <div class="mt-2">
                <div wire:loading.block wire:target="refresh">
                    <x-ui.skeleton class="h-9 w-16" />
                </div>
                <div wire:loading.remove wire:target="refresh" class="text-3xl font-bold tracking-tight tabular-nums">
                    {{ number_format($pending_access_requests, 0, ',', '.') }}
                </div>
            </div>
            <p class="mt-2 text-xs text-slate-600 dark:text-slate-400">Solicitações em análise.</p>
        </x-ui.card>
    @endif
</div>
