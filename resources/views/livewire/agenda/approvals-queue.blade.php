<x-ui.card title="{{ __('Aprovações pendentes') }}">
    @php
        $rows = $approvals;
    @endphp

    <ul class="divide-y divide-slate-200 dark:divide-slate-800">
        @forelse ($rows as $a)
            @php($ev = $a->approvable)
            @php($title = $ev instanceof \App\Models\Event ? $ev->title : '#'.$a->approvable_id)
            <li wire:key="ap-{{ $a->id }}" class="grid gap-4 py-5 lg:grid-cols-[minmax(0,1.4fr)_minmax(16rem,0.8fr)] lg:items-start">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ $title }}</h3>
                        @if ($ev instanceof \App\Models\Event)
                            <x-ui.badge :tone="$ev->statusTone()">{{ $ev->statusLabel() }}</x-ui.badge>
                        @endif
                    </div>
                    @if ($ev instanceof \App\Models\Event)
                        <p class="mt-1 text-sm text-slate-700 dark:text-slate-200">
                            {{ $ev->starts_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? 'Data não informada' }}
                            · {{ $ev->location?->name ?: 'Local não informado' }}
                        </p>
                    @endif
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Pedido por {{ $a->requestedBy?->name ?? 'alguém sem nome' }}</p>
                </div>

                <div>
                    <label for="approval-note-{{ $a->id }}" class="text-xs font-semibold text-slate-700 dark:text-slate-200">Justificativa de {{ $title }}</label>
                    <textarea
                        id="approval-note-{{ $a->id }}"
                        wire:model="notes.{{ $a->id }}"
                        rows="2"
                        class="mt-1 w-full min-h-11 rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950"
                    ></textarea>
                    @error('notes.'.$a->id)
                        <p class="mt-1 text-sm text-rose-700 dark:text-rose-300" role="alert">{{ $message }}</p>
                    @enderror
                    <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                        <button
                            type="button"
                            wire:click="approve({{ $a->id }})"
                            wire:confirm="Aprovar {{ $title }}? O evento fica publicado."
                            class="inline-flex min-h-11 flex-1 items-center justify-center rounded-xl bg-emerald-700 px-4 text-sm font-semibold text-white"
                        >Aprovar</button>
                        <button
                            type="button"
                            wire:click="reject({{ $a->id }})"
                            wire:confirm="Rejeitar {{ $title }}? O evento não entra na agenda publicada."
                            class="inline-flex min-h-11 flex-1 items-center justify-center rounded-xl bg-rose-700 px-4 text-sm font-semibold text-white"
                        >Rejeitar</button>
                    </div>
                </div>
            </li>
        @empty
            <li class="py-6">
                <x-nexus.empty-state :title="__('Nada pendente')" :description="__('Quando houver um culto para aprovar, a data, o local e a justificativa aparecem aqui.')" />
            </li>
        @endforelse
    </ul>

    <div class="mt-4">{{ $approvals->links() }}</div>
</x-ui.card>
