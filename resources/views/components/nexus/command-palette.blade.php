@php
    $commands = [];
    if (auth()->check()) {
        $commands[] = ['id' => 'dash', 'label' => __('Ir para o painel'), 'href' => route('dashboard'), 'keywords' => 'dashboard painel inicio g d'];
        if (auth()->user()->can('atuar_como_facilitador') || auth()->user()->can('aprovar_acesso')) {
            $commands[] = ['id' => 'access', 'label' => __('Pedidos de acesso'), 'href' => route('access.requests.index'), 'keywords' => 'acesso pedidos'];
        }
        if (auth()->user()->can('viewAny', \App\Models\Approval::class)) {
            $commands[] = ['id' => 'approvals', 'label' => __('Fila de aprovações'), 'href' => route('agenda.approvals.index'), 'keywords' => 'aprovações'];
        }
        if (auth()->user()->can('viewAny', \App\Models\Event::class)) {
            $commands[] = ['id' => 'events', 'label' => __('Eventos'), 'href' => route('agenda.events.index'), 'keywords' => 'eventos agenda g e'];
            if (auth()->user()->can('create', \App\Models\Event::class)) {
                $commands[] = ['id' => 'new-event', 'label' => __('Novo evento'), 'href' => route('agenda.events.create'), 'keywords' => 'criar evento n e'];
            }
        }
        if (auth()->user()->can('viewAny', \App\Models\RoomReservation::class)) {
            $commands[] = ['id' => 'reservations', 'label' => __('Reservas'), 'href' => route('infrastructure.room-reservations.index'), 'keywords' => 'reservas salas g r'];
            $commands[] = ['id' => 'calendar', 'label' => __('Calendário de salas'), 'href' => route('infrastructure.room-reservations.calendar'), 'keywords' => 'calendário salas'];
            if (auth()->user()->can('create', \App\Models\RoomReservation::class)) {
                $commands[] = ['id' => 'new-res', 'label' => __('Nova reserva'), 'href' => route('infrastructure.room-reservations.create'), 'keywords' => 'criar reserva n r'];
            }
        }
        if (auth()->user()->can('viewAny', \App\Models\User::class)) {
            $commands[] = ['id' => 'users', 'label' => __('Usuários'), 'href' => route('users.index'), 'keywords' => 'usuarios utilizadores users g u'];
        }
        if (auth()->user()->can('viewUserRoleDirectory')) {
            $commands[] = ['id' => 'roles', 'label' => __('Papéis'), 'href' => route('users.roles'), 'keywords' => 'papéis roles'];
        }
        if (auth()->user()->can('gerenciar_avisos')) {
            $commands[] = ['id' => 'wa', 'label' => __('WhatsApp'), 'href' => route('agenda.whatsapp.index'), 'keywords' => 'whatsapp avisos'];
            $commands[] = ['id' => 'wa-tpl', 'label' => __('Templates WhatsApp'), 'href' => route('agenda.whatsapp-templates.index'), 'keywords' => 'templates whatsapp'];
            $commands[] = ['id' => 'event-type-tpl', 'label' => __('Modelos dos tipos de evento'), 'href' => route('agenda.event-type-templates'), 'keywords' => 'modelo template descricao rrm ra organistas'];
        }
        if (auth()->user()->isSuperAdmin() || auth()->user()->can('visualizar_relatorios')) {
            $commands[] = ['id' => 'baptism', 'label' => __('Lista de batismos'), 'href' => route('secretaria.baptism-list'), 'keywords' => 'secretaria batismos boletim'];
            $commands[] = ['id' => 'markings', 'label' => __('Marcações'), 'href' => route('secretaria.markings'), 'keywords' => 'secretaria marcacoes'];
            $commands[] = ['id' => 'quem-atende', 'label' => __('Quem atende'), 'href' => route('secretaria.quem-atende'), 'keywords' => 'secretaria comum igrejas ministerio'];
        }
        if (auth()->user()->isSuperAdmin() || auth()->user()->can('editar_evento')) {
            $commands[] = ['id' => 'notices', 'label' => __('Avisos do boletim'), 'href' => route('secretaria.notices'), 'keywords' => 'secretaria avisos'];
            $commands[] = ['id' => 'changes', 'label' => __('Alterações de culto'), 'href' => route('secretaria.schedule-changes'), 'keywords' => 'secretaria alteracoes'];
            $commands[] = ['id' => 'collections', 'label' => __('Coletas'), 'href' => route('secretaria.collections'), 'keywords' => 'secretaria coletas'];
        }
        $commands[] = ['id' => 'profile', 'label' => __('Perfil'), 'href' => route('profile'), 'keywords' => 'perfil'];
    }
@endphp

<div
    x-data="nexusCommandPalette(@js($commands))"
    x-on:keydown.window="onGlobalKey($event)"
    x-cloak
>
    <template x-if="open">
    <div
        class="fixed inset-0 z-[80] flex items-start justify-center bg-slate-950/50 px-4 pt-[12vh]"
        @click.self="close()"
        @keydown.escape.window="if (!$store.nexusDialog?.open) close()"
    >
        <div
            class="w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-900"
            role="dialog"
            aria-modal="true"
            aria-label="{{ __('Paleta de comandos') }}"
        >
            <div class="border-b border-slate-100 p-3 dark:border-slate-800">
                <input
                    x-ref="input"
                    type="search"
                    x-model="query"
                    @keydown.arrow-down.prevent="move(1)"
                    @keydown.arrow-up.prevent="move(-1)"
                    @keydown.enter.prevent="activate()"
                    class="min-h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-base dark:border-slate-700 dark:bg-slate-950"
                    aria-label="{{ __('Buscar páginas e ações') }}"
                    placeholder="{{ __('Ir para… (Ctrl+K)') }}"
                />
            </div>
            <ul class="max-h-72 overflow-y-auto py-1">
                <template x-for="(cmd, idx) in filtered" :key="cmd.id">
                    <li>
                        <a
                            :href="cmd.href"
                            wire:navigate
                            class="flex items-center justify-between px-4 py-2.5 text-sm"
                            :class="idx === active ? 'bg-primary-600 text-white' : 'text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800'"
                            @mouseenter="active = idx"
                            @click="close()"
                        >
                            <span x-text="cmd.label"></span>
                        </a>
                    </li>
                </template>
                <li x-show="filtered.length === 0" class="px-4 py-6 text-center text-xs text-slate-500">{{ __('Nenhum comando') }}</li>
            </ul>
            <div class="border-t border-slate-100 px-3 py-2 text-[10px] text-slate-400 dark:border-slate-800">
                {{ __('Atalhos: g d/e/r/u · n e/r · ? · Esc') }}
            </div>
        </div>
    </div>
    </template>

    <template x-if="cheatsheet">
    <div
        class="fixed inset-0 z-[70] flex items-center justify-center bg-slate-950/40 p-4"
        @click.self="cheatsheet = false"
    >
        <div class="w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-4 text-sm shadow-xl dark:border-slate-700 dark:bg-slate-900">
            <div class="font-semibold">{{ __('Atalhos de teclado') }}</div>
            <ul class="mt-3 space-y-1 text-xs text-slate-600 dark:text-slate-300">
                <li><kbd class="rounded bg-slate-100 px-1 dark:bg-slate-800">Ctrl+K</kbd> / <kbd class="rounded bg-slate-100 px-1 dark:bg-slate-800">/</kbd> — {{ __('Paleta') }}</li>
                <li><kbd class="rounded bg-slate-100 px-1 dark:bg-slate-800">g</kbd> + <kbd class="rounded bg-slate-100 px-1 dark:bg-slate-800">d/e/r/u</kbd> — {{ __('Navegar') }}</li>
                <li><kbd class="rounded bg-slate-100 px-1 dark:bg-slate-800">n</kbd> + <kbd class="rounded bg-slate-100 px-1 dark:bg-slate-800">e/r</kbd> — {{ __('Novo evento/reserva') }}</li>
                <li><kbd class="rounded bg-slate-100 px-1 dark:bg-slate-800">?</kbd> — {{ __('Esta ajuda') }}</li>
            </ul>
            <button type="button" class="mt-4 min-h-[44px] w-full rounded-xl bg-slate-100 text-sm font-medium dark:bg-slate-800" @click="cheatsheet = false">{{ __('Fechar') }}</button>
        </div>
    </div>
    </template>
</div>
