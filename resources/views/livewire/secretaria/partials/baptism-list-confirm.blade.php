@if ($report && in_array($screen, ['listagem', 'versoes'], true))
    <section aria-labelledby="confirm-baptism-heading" class="rounded-xl border border-slate-200 bg-white px-4 py-4 dark:border-slate-800 dark:bg-slate-950">
        <h2 id="confirm-baptism-heading" class="text-base font-semibold text-slate-900 dark:text-slate-100">Verificação</h2>
        <p class="mt-1 max-w-2xl text-sm leading-relaxed text-slate-600 dark:text-slate-300">Marque se você conferiu esta revisão. O nome e a hora ficam na lista. A publicação só abre depois de pelo menos uma verificação.</p>

        @if ($confirmationBlocked && ! $checkedByMe)
            <p class="mt-3 text-sm font-semibold text-amber-950 dark:text-amber-100" role="status">Publique ou oculte os itens pendentes antes de marcar verificado.</p>
        @elseif ($canCheckList)
            <button
                type="button"
                x-data
                x-on:click="$dispatch('open-modal', 'baptism-check')"
                aria-pressed="{{ $checkedByMe ? 'true' : 'false' }}"
                class="mt-3 inline-flex min-h-11 items-center justify-center rounded-xl px-4 py-2 text-sm font-semibold focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 {{ $checkedByMe ? 'bg-primary-600 text-white' : 'border border-slate-200 bg-white text-slate-800 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100' }}"
            >{{ $checkedByMe ? 'Verificado' : 'Marcar verificado' }}</button>
            @error('list_check')
                <p class="mt-1 text-sm text-rose-700" role="alert">{{ $message }}</p>
            @enderror
            <x-modal name="baptism-check" focusable maxWidth="md">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $checkedByMe ? 'Retirar a verificação?' : 'Confirmar verificação?' }}</h2>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                        {{ $checkedByMe ? 'Seu nome sai da lista de quem verificou.' : 'Seu nome e a hora ficam registrados como verificação.' }}
                    </p>
                    <div class="mt-6 flex flex-wrap justify-end gap-2">
                        <button type="button" x-on:click="$dispatch('close')" class="inline-flex min-h-11 items-center rounded-xl border border-slate-200 px-4 text-sm font-semibold text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-slate-800 dark:text-slate-100">Cancelar</button>
                        <button type="button" wire:click="toggleCheck" x-on:click="$dispatch('close')" class="inline-flex min-h-11 items-center rounded-xl bg-primary-600 px-4 text-sm font-semibold text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">{{ $checkedByMe ? 'Retirar' : 'Confirmar' }}</button>
                    </div>
                </div>
            </x-modal>
        @endif

        <h3 class="mt-4 text-sm font-semibold text-slate-900 dark:text-slate-100">Quem verificou</h3>
        @if ($checks->isEmpty())
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Nenhuma verificação ainda.</p>
        @else
            <ul class="mt-2 divide-y divide-slate-200 dark:divide-slate-800">
                @foreach ($checks as $check)
                    <li class="flex flex-col gap-0.5 py-2 text-sm text-slate-800 sm:flex-row sm:items-center sm:justify-between dark:text-slate-100">
                        <span class="font-semibold">{{ $check->user?->name ?? 'Pessoa' }}</span>
                        <span class="text-slate-600 dark:text-slate-300">{{ $check->checked_at->timezone(\App\Services\BaptismListDocumentBlocks::DISPLAY_TIMEZONE)->format('d/m/Y H:i') }}</span>
                    </li>
                @endforeach
            </ul>
        @endif

        <div class="mt-4 border-t border-slate-200 pt-4 dark:border-slate-800">
            <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">Aprovação da publicação</h3>
            @if ($rejectionReason !== '' && $report->status !== \App\Models\BaptismListReport::STATUS_READY)
                <p class="mt-3 rounded-xl bg-rose-50 px-3 py-2 text-sm text-rose-800 dark:bg-rose-950/40 dark:text-rose-200" role="status">
                    Reprovada
                    @if ($rejectorName)
                        por {{ $rejectorName }}
                    @endif
                    @if ($rejectedAtLabel)
                        · {{ $rejectedAtLabel }}
                    @endif
                    . Ajuste: {{ $rejectionReason }}
                </p>
            @endif
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
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">A confirmação publica esta revisão. Não há campo de observação.</p>
            @else
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Há verificação. A aprovação final fica com quem escreve na secretaria.</p>
            @endif

            @if ($canApprovePublication && $report->status !== \App\Models\BaptismListReport::STATUS_READY)
                <div class="mt-3 flex flex-wrap gap-2">
                    @if ($checks->isNotEmpty() && ! $confirmationBlocked)
                        <button type="button" x-data x-on:click="$dispatch('open-modal', 'baptism-approve')" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">Aprovar publicação</button>
                    @endif
                    <button type="button" x-data x-on:click="$dispatch('open-modal', 'baptism-reject')" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-slate-800 dark:text-slate-100">Reprovar</button>
                </div>
                @if ($checks->isNotEmpty() && ! $confirmationBlocked)
                    <x-modal name="baptism-approve" focusable maxWidth="md">
                        <div class="p-6">
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Aprovar a publicação?</h2>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Esta revisão passa a versão validada. Não é preciso escrever observação.</p>
                            <div class="mt-6 flex flex-wrap justify-end gap-2">
                                <button type="button" x-on:click="$dispatch('close')" class="inline-flex min-h-11 items-center rounded-xl border border-slate-200 px-4 text-sm font-semibold text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-slate-800 dark:text-slate-100">Cancelar</button>
                                <button type="button" wire:click="approvePublication" x-on:click="$dispatch('close')" class="inline-flex min-h-11 items-center rounded-xl bg-primary-600 px-4 text-sm font-semibold text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">Aprovar publicação</button>
                            </div>
                        </div>
                    </x-modal>
                @endif
                <x-modal name="baptism-reject" :show="$errors->has('rejection_reason')" focusable maxWidth="md">
                    <form wire:submit="rejectPublication" class="p-6" x-on:baptism-rejected.window="$dispatch('close')">
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Reprovar a lista</h2>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Escreva o que precisa ser ajustado. A lista continua em rascunho.</p>
                        <label for="baptism-rejection-reason" class="mt-4 block text-xs font-semibold text-slate-700 dark:text-slate-200">O que precisa ajustar</label>
                        <textarea id="baptism-rejection-reason" wire:model="rejection_reason" rows="3" class="mt-1 w-full min-h-11 rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950"></textarea>
                        @error('rejection_reason')
                            <p class="mt-1 text-sm text-rose-700" role="alert">{{ $message }}</p>
                        @enderror
                        <div class="mt-6 flex flex-wrap justify-end gap-2">
                            <button type="button" x-on:click="$dispatch('close')" class="inline-flex min-h-11 items-center rounded-xl border border-slate-200 px-4 text-sm font-semibold text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-slate-800 dark:text-slate-100">Cancelar</button>
                            <button type="submit" class="inline-flex min-h-11 items-center rounded-xl border border-rose-200 bg-rose-50 px-4 text-sm font-semibold text-rose-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">Reprovar</button>
                        </div>
                    </form>
                </x-modal>
            @endif
        </div>
    </section>
@endif
