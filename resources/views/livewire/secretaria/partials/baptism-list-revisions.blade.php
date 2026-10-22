<section aria-labelledby="revisions-heading" class="rounded-xl border border-slate-200 dark:border-slate-800">
    <div class="border-b border-slate-200 px-4 py-3 dark:border-slate-800">
        <h2 id="revisions-heading" class="text-sm font-semibold text-slate-900 dark:text-slate-100">Versões da lista</h2>
        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Cada revisão guarda a emissão da regional. Abra uma para voltar à listagem.</p>
    </div>
    @if ($revisions->isEmpty())
        <div class="p-4">
            <x-nexus.empty-state title="Nenhuma versão" description="Gere a lista para criar a revisão R00." />
        </div>
    @else
        <ul class="divide-y divide-slate-200 dark:divide-slate-800">
            @foreach ($revisions as $revision)
                @php
                    $statusLabel = match ($revision->status) {
                        'ready' => 'Validada',
                        'archived' => 'Arquivada',
                        default => 'Rascunho',
                    };
                @endphp
                <li class="flex flex-col gap-2 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                            {{ $revision->issue_date->format('d/m/Y') }} · R{{ sprintf('%02d', $revision->revision) }}
                            @if ((int) $revision->id === (int) ($report?->id))
                                <span class="ml-2 text-xs font-semibold text-primary-700 dark:text-primary-300">Aberta</span>
                            @endif
                        </p>
                        <p class="text-sm text-slate-600 dark:text-slate-300">{{ $statusLabel }}@if ($revision->generated_at) · {{ $revision->generated_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }} @endif</p>
                    </div>
                    <button type="button" wire:click="selectRevision({{ $revision->id }})" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 px-4 text-sm font-semibold text-slate-800 dark:border-slate-800 dark:text-slate-100">Abrir listagem</button>
                </li>
            @endforeach
        </ul>
    @endif
</section>
