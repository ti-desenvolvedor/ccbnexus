@if ($canConfirmList && in_array($screen, ['listagem', 'versoes'], true))
    <section aria-labelledby="confirm-baptism-heading" class="rounded-xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-800 dark:bg-slate-950">
        <h2 id="confirm-baptism-heading" class="text-sm font-semibold text-slate-900 dark:text-slate-100">Confirmar lista</h2>
        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Esta revisão passa a versão validada. A justificativa fica registrada.</p>
        @if ($confirmationBlocked)
            <p class="mt-3 text-sm font-semibold text-amber-950 dark:text-amber-100" role="status">Publique ou oculte os itens pendentes antes de confirmar a lista.</p>
        @else
            <label for="baptism-confirmation-justification" class="mt-3 block text-xs font-semibold text-slate-700 dark:text-slate-200">Justificativa</label>
            <textarea id="baptism-confirmation-justification" wire:model="confirmation_justification" rows="2" class="mt-1 w-full min-h-11 rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950"></textarea>
            @error('confirmation_justification')
                <p class="mt-1 text-sm text-rose-700" role="alert">{{ $message }}</p>
            @enderror
            <button type="button" wire:click="confirmList" wire:confirm="Confirmar esta revisão? Ela passa a versão validada." class="mt-3 inline-flex min-h-11 max-w-full items-center justify-center whitespace-normal rounded-xl bg-primary-600 px-4 py-2 text-center text-sm font-semibold leading-snug text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">Confirmar lista</button>
        @endif
    </section>
@elseif ($report && $report->status === \App\Models\BaptismListReport::STATUS_READY && in_array($screen, ['listagem', 'versoes'], true))
    <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">Versão validada R{{ sprintf('%02d', $report->revision) }}. Gerar de novo devolve a lista a rascunho.</p>
@endif
