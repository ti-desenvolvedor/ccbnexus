@php
    $checkIcon = '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
    $checkIconLg = '<svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
    $smallCheck = '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
    $undoIcon = '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M3 12a9 9 0 109-9"/><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M3 4v5h5"/></svg>';
    $undoIconLg = '<svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M3 12a9 9 0 109-9"/><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M3 4v5h5"/></svg>';
    $sealIcon = '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><circle cx="12" cy="12" r="8" stroke-width="2"/><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M8.5 12.5l2.2 2.2 4.8-5"/></svg>';
    $sealIconLg = '<svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><circle cx="12" cy="12" r="8" stroke-width="2"/><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M8.5 12.5l2.2 2.2 4.8-5"/></svg>';
    $adjustIcon = '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M12 8v5"/><circle cx="12" cy="16.5" r="0.8" fill="currentColor" stroke="none"/><circle cx="12" cy="12" r="8" stroke-width="2"/></svg>';
    $adjustIconLg = '<svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M12 8v5"/><circle cx="12" cy="16.5" r="0.8" fill="currentColor" stroke="none"/><circle cx="12" cy="12" r="8" stroke-width="2"/></svg>';
    $closeIcon = '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-width="2" stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>';
@endphp

@if ($report && in_array($screen, ['listagem', 'versoes'], true))
    <section aria-labelledby="confirm-baptism-heading" class="rounded-xl border border-slate-200 bg-white px-4 py-4 dark:border-slate-800 dark:bg-slate-950">
        <div class="flex items-start gap-3">
            <span class="mt-0.5 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-100">{!! $checkIcon !!}</span>
            <div class="min-w-0">
                <h2 id="confirm-baptism-heading" class="text-base font-semibold text-slate-900 dark:text-slate-100">Verificação</h2>
                <p class="mt-1 max-w-2xl text-sm leading-relaxed text-slate-600 dark:text-slate-300">Marque se você conferiu esta revisão. O nome e a hora ficam na lista. A publicação só abre depois de pelo menos uma verificação.</p>
            </div>
        </div>

        @if ($confirmationBlocked && ! $checkedByMe)
            <p class="mt-3 text-sm font-semibold text-amber-950 dark:text-amber-100" role="status">Publique ou oculte os itens pendentes antes de marcar verificado.</p>
        @elseif ($canCheckList)
            <button
                type="button"
                x-data
                x-on:click="$dispatch('open-modal', 'baptism-check')"
                aria-pressed="{{ $checkedByMe ? 'true' : 'false' }}"
                class="mt-3 inline-flex min-h-11 items-center justify-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 {{ $checkedByMe ? 'bg-primary-600 text-white' : 'border border-slate-200 bg-white text-slate-800 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100' }}"
            >
                {!! $smallCheck !!}
                {{ $checkedByMe ? 'Verificado' : 'Marcar verificado' }}
            </button>
            @error('list_check')
                <p class="mt-1 text-sm text-rose-700" role="alert">{{ $message }}</p>
            @enderror
            <x-modal name="baptism-check" focusable maxWidth="md">
                <div role="dialog" aria-modal="true" aria-labelledby="baptism-check-title" class="bg-white dark:bg-slate-950">
                    <header class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-4 {{ $checkedByMe ? 'bg-rose-50 dark:border-slate-800 dark:bg-rose-950/40' : 'bg-emerald-50 dark:border-slate-800 dark:bg-emerald-950/40' }}">
                        <h2 id="baptism-check-title" class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ $checkedByMe ? 'Retirar a verificação?' : 'Confirmar verificação?' }}</h2>
                        <button type="button" x-on:click="$dispatch('close')" aria-label="Fechar" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-slate-600 hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:text-slate-300 dark:hover:bg-slate-900">{!! $closeIcon !!}</button>
                    </header>
                    <div class="flex items-center gap-4 px-4 py-6">
                        @if ($checkedByMe)
                            <span class="inline-flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-rose-50 text-rose-800 dark:bg-rose-950/40 dark:text-rose-200">{!! $undoIconLg !!}</span>
                        @else
                            <span class="inline-flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-100">{!! $checkIconLg !!}</span>
                        @endif
                        <p class="min-w-0 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                            {{ $checkedByMe ? 'Seu nome sai da lista de quem verificou.' : 'Seu nome e a hora ficam registrados como verificação.' }}
                        </p>
                    </div>
                    <footer class="flex flex-col gap-2 border-t border-slate-200 bg-slate-50 px-4 py-4 sm:flex-row sm:justify-end dark:border-slate-800 dark:bg-slate-900">
                        <button type="button" x-on:click="$dispatch('close')" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100">Cancelar</button>
                        <button type="button" wire:click="toggleCheck" x-on:click="$dispatch('close')" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-primary-600 px-4 text-sm font-semibold text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                            {!! $checkedByMe ? $undoIcon : $smallCheck !!}
                            {{ $checkedByMe ? 'Retirar' : 'Confirmar' }}
                        </button>
                    </footer>
                </div>
            </x-modal>
        @endif

        <h3 class="mt-5 text-sm font-semibold text-slate-900 dark:text-slate-100">Quem verificou</h3>
        @if ($checks->isEmpty())
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Nenhuma verificação ainda.</p>
        @else
            <ul class="mt-2 divide-y divide-slate-200 dark:divide-slate-800">
                @foreach ($checks as $check)
                    <li class="flex items-center justify-between gap-3 py-2 text-sm text-slate-800 dark:text-slate-100">
                        <span class="inline-flex min-w-0 items-center gap-2 font-semibold">
                            <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-100">{!! $smallCheck !!}</span>
                            <span class="truncate">{{ $check->user?->name ?? 'Pessoa' }}</span>
                        </span>
                        <span class="shrink-0 text-slate-600 dark:text-slate-300">{{ $check->checked_at->timezone(\App\Services\BaptismListDocumentBlocks::DISPLAY_TIMEZONE)->format('d/m/Y H:i') }}</span>
                    </li>
                @endforeach
            </ul>
        @endif

        <div class="mt-5 border-t border-slate-200 pt-4 dark:border-slate-800">
            <div class="flex items-start gap-3">
                <span class="mt-0.5 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-50 text-primary-700 dark:bg-slate-800 dark:text-primary-100">{!! $sealIcon !!}</span>
                <div class="min-w-0">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">Aprovação da publicação</h3>
                    @if ($report->status === \App\Models\BaptismListReport::STATUS_READY)
                        <p class="mt-1 text-sm font-semibold text-slate-800 dark:text-slate-100">
                            Publicação aprovada
                            @if ($approverName)
                                por {{ $approverName }}
                            @endif
                            @if ($approvedAtLabel)
                                · {{ $approvedAtLabel }}
                            @endif
                            · R{{ sprintf('%02d', $report->revision) }}.
                        </p>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Gerar de novo devolve a lista a rascunho e apaga as verificações.</p>
                    @elseif ($checks->isEmpty())
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Falta pelo menos uma verificação para liberar a aprovação final.</p>
                    @elseif ($confirmationBlocked)
                        <p class="mt-1 text-sm font-semibold text-amber-950 dark:text-amber-100" role="status">Publique ou oculte os itens pendentes antes de aprovar a publicação.</p>
                    @elseif ($canApprovePublication)
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">A confirmação publica esta revisão.</p>
                    @else
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Há verificação. A aprovação final fica com quem escreve na secretaria.</p>
                    @endif
                </div>
            </div>

            @if ($rejectionReason !== '' && $report->status !== \App\Models\BaptismListReport::STATUS_READY)
                <p class="mt-3 flex items-start gap-2 rounded-xl bg-rose-50 px-3 py-2 text-sm text-rose-800 dark:bg-rose-950/40 dark:text-rose-200" role="status">
                    <span class="mt-0.5 shrink-0">{!! $adjustIcon !!}</span>
                    <span>
                        Reprovada
                        @if ($rejectorName)
                            por {{ $rejectorName }}
                        @endif
                        @if ($rejectedAtLabel)
                            · {{ $rejectedAtLabel }}
                        @endif
                        . Ajuste: {{ $rejectionReason }}
                    </span>
                </p>
            @endif

            @if ($canApprovePublication && $report->status !== \App\Models\BaptismListReport::STATUS_READY)
                <div class="mt-3 flex flex-wrap gap-2">
                    @if ($checks->isNotEmpty() && ! $confirmationBlocked)
                        <button type="button" x-data x-on:click="$dispatch('open-modal', 'baptism-approve')" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                            {!! $smallCheck !!}
                            Aprovar publicação
                        </button>
                    @endif
                    <button type="button" x-data x-on:click="$dispatch('open-modal', 'baptism-reject')" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-200">
                        {!! $adjustIcon !!}
                        Reprovar
                    </button>
                </div>
                @if ($checks->isNotEmpty() && ! $confirmationBlocked)
                    <x-modal name="baptism-approve" focusable maxWidth="md">
                        <div role="dialog" aria-modal="true" aria-labelledby="baptism-approve-title" class="bg-white dark:bg-slate-950">
                            <header class="flex items-center justify-between gap-3 border-b border-slate-200 bg-emerald-50 px-4 py-4 dark:border-slate-800 dark:bg-emerald-950/40">
                                <h2 id="baptism-approve-title" class="text-base font-semibold text-slate-900 dark:text-slate-100">Aprovar a publicação?</h2>
                                <button type="button" x-on:click="$dispatch('close')" aria-label="Fechar" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-slate-600 hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:text-slate-300 dark:hover:bg-slate-900">{!! $closeIcon !!}</button>
                            </header>
                            <div class="flex items-center gap-4 px-4 py-6">
                                <span class="inline-flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-100">{!! $sealIconLg !!}</span>
                                <p class="min-w-0 text-sm leading-relaxed text-slate-600 dark:text-slate-300">Esta revisão passa a versão validada.</p>
                            </div>
                            <footer class="flex flex-col gap-2 border-t border-slate-200 bg-slate-50 px-4 py-4 sm:flex-row sm:justify-end dark:border-slate-800 dark:bg-slate-900">
                                <button type="button" x-on:click="$dispatch('close')" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100">Cancelar</button>
                                <button type="button" wire:click="approvePublication" x-on:click="$dispatch('close')" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-primary-600 px-4 text-sm font-semibold text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                                    {!! $smallCheck !!}
                                    Aprovar publicação
                                </button>
                            </footer>
                        </div>
                    </x-modal>
                @endif
                <x-modal name="baptism-reject" :show="$errors->has('rejection_reason')" focusable maxWidth="md">
                    <form wire:submit="rejectPublication" role="dialog" aria-modal="true" aria-labelledby="baptism-reject-title" class="bg-white dark:bg-slate-950" x-on:baptism-rejected.window="$dispatch('close')">
                        <header class="flex items-center justify-between gap-3 border-b border-slate-200 bg-rose-50 px-4 py-4 dark:border-slate-800 dark:bg-rose-950/40">
                            <h2 id="baptism-reject-title" class="text-base font-semibold text-slate-900 dark:text-slate-100">Reprovar a lista</h2>
                            <button type="button" x-on:click="$dispatch('close')" aria-label="Fechar" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-slate-600 hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:text-slate-300 dark:hover:bg-slate-900">{!! $closeIcon !!}</button>
                        </header>
                        <div class="px-4 py-6">
                            <div class="flex items-center gap-4">
                                <span class="inline-flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-rose-50 text-rose-800 dark:bg-rose-950/40 dark:text-rose-200">{!! $adjustIconLg !!}</span>
                                <p class="min-w-0 text-sm leading-relaxed text-slate-600 dark:text-slate-300">Escreva o que precisa ser ajustado. A lista continua em rascunho.</p>
                            </div>
                            <label for="baptism-rejection-reason" class="mt-4 block text-xs font-semibold text-slate-700 dark:text-slate-200">O que precisa ajustar</label>
                            <textarea id="baptism-rejection-reason" wire:model="rejection_reason" rows="3" class="mt-1 w-full min-h-11 rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950"></textarea>
                            @error('rejection_reason')
                                <p class="mt-1 text-sm text-rose-700" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                        <footer class="flex flex-col gap-2 border-t border-slate-200 bg-slate-50 px-4 py-4 sm:flex-row sm:justify-end dark:border-slate-800 dark:bg-slate-900">
                            <button type="button" x-on:click="$dispatch('close')" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100">Cancelar</button>
                            <button type="submit" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-rose-600 px-4 text-sm font-semibold text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">Reprovar</button>
                        </footer>
                    </form>
                </x-modal>
            @endif
        </div>
    </section>
@endif
