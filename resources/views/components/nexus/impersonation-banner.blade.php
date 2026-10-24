@auth
    @php($banner = app(\App\Services\Auth\ImpersonationService::class)->banner())
    @if ($banner)
        <section class="border-b border-amber-200 bg-amber-50 px-4 py-3 text-amber-950 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-100" aria-label="Personificação ativa">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex min-w-0 flex-1 items-start gap-3">
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-950 dark:bg-amber-950 dark:text-amber-100">
                    <x-nexus.glyph name="eye" class="h-5 w-5" />
                </span>
                <dl class="grid min-w-0 flex-1 gap-3 sm:grid-cols-3">
                    <div class="min-w-0">
                        <dt class="text-xs font-semibold">Você</dt>
                        <dd class="truncate text-sm font-semibold">{{ $banner['actor']->name }}</dd>
                        <dd class="truncate text-xs">{{ $banner['actor']->email }}</dd>
                    </div>
                    <div class="min-w-0">
                        <dt class="text-xs font-semibold">Vendo como</dt>
                        <dd class="truncate text-sm font-semibold">{{ $banner['as']->name }}</dd>
                        <dd class="truncate text-xs">{{ $banner['as']->email }}</dd>
                        <dd class="truncate text-xs">{{ $banner['scope'] }}</dd>
                    </div>
                    <div
                        class="min-w-0"
                        @if ($banner['startedAt'])
                            x-data="{
                                started: {{ (int) $banner['startedAt'] }},
                                now: Date.now(),
                                timer: null,
                                init() {
                                    this.timer = setInterval(() => { this.now = Date.now() }, 1000)
                                },
                                destroy() {
                                    clearInterval(this.timer)
                                },
                                label() {
                                    const total = Math.max(0, Math.floor((this.now / 1000) - this.started))
                                    const h = Math.floor(total / 3600)
                                    const m = Math.floor((total % 3600) / 60)
                                    const s = total % 60
                                    const p = (n) => String(n).padStart(2, '0')
                                    return p(h) + ':' + p(m) + ':' + p(s)
                                }
                            }"
                        @endif
                    >
                        <dt class="text-xs font-semibold">Nesta sessão</dt>
                        <dd class="text-sm font-semibold tabular-nums" x-text="label()">{{ $banner['elapsed'] }}</dd>
                        <dd class="text-xs">desde {{ $banner['since'] }}</dd>
                    </div>
                </dl>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <p class="max-w-xs text-xs">As ações usam as permissões de {{ $banner['as']->name }}. Encerre antes de se afastar do computador.</p>
                    <form method="POST" action="{{ route('impersonation.stop') }}" class="shrink-0">
                        @csrf
                        <button type="submit" class="inline-flex min-h-11 items-center rounded-lg bg-amber-950 px-4 text-sm font-semibold text-white hover:bg-amber-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-950 focus-visible:ring-offset-2 focus-visible:ring-offset-amber-50">
                            Encerrar
                        </button>
                    </form>
                </div>
            </div>
        </section>
    @endif
@endauth
