<div>
    <div class="grid gap-8 lg:grid-cols-2 lg:items-center">
        <div>
            <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200">
                <span class="inline-flex h-2 w-2 rounded-full bg-primary-500"></span>
                Plataforma modular • Laravel + Livewire
            </div>

            <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-900 dark:text-slate-100 sm:text-5xl">
                CCB Nexus
            </h1>
            <p class="mt-4 max-w-prose text-base leading-relaxed text-slate-600 dark:text-slate-300">
                Agenda, Eventos, Secretaria, Avisos/Notificações, Aprovações, Relatórios e Usuários/Permissões — com escopo por Regional/Administração/Casa.
            </p>

            <div class="mt-6 flex flex-wrap gap-3">
                @if (Route::has('login'))
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-xl bg-primary-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-primary-700">
                        Entrar no sistema
                        <span class="ml-2">→</span>
                    </a>
                @endif
                <a href="{{ route('status') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-900 shadow-sm hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100 dark:hover:bg-slate-900">
                    Ver status
                </a>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/40">
            <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Destaques</h2>
            <div class="mt-4 grid gap-3">
                <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                    <div class="font-semibold">Contexto organizacional</div>
                    <div class="mt-1 text-sm text-slate-600 dark:text-slate-300">Regional → Administração → Casa de oração (sem vazamento de dados).</div>
                </div>
                <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                    <div class="font-semibold">Recorrência e previsões</div>
                    <div class="mt-1 text-sm text-slate-600 dark:text-slate-300">Séries semanais/mensais/anuais com preview e limites seguros.</div>
                </div>
                <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                    <div class="font-semibold">Avisos WhatsApp (manual)</div>
                    <div class="mt-1 text-sm text-slate-600 dark:text-slate-300">Templates moderados, geração/edição, copiar e `wa.me`.</div>
                </div>
            </div>
        </div>
    </div>
</div>

