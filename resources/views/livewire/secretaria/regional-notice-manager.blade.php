<div class="space-y-4">
    <div>
        <h1 class="text-xl font-semibold">Avisos do boletim</h1>
        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
            Permanentes (Halloween, Eleições…) entram em todas as listas. Os demais são específicos por período de vigência.
        </p>
    </div>
    @if (session('status')) <p class="text-sm text-emerald-700">{{ session('status') }}</p> @endif
    <form wire:submit="save" class="grid gap-3 rounded-xl border border-slate-200 p-4 dark:border-slate-800">
        <input type="text" wire:model="title" placeholder="Título" class="rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
        <textarea wire:model="body" rows="4" placeholder="Texto" class="rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950"></textarea>
        <x-nexus.searchable-select
            wire:model="category"
            search-placeholder="Buscar categoria..."
            :options="[
                ['value' => 'geral', 'label' => 'Geral'],
                ['value' => 'bras', 'label' => 'Brás'],
                ['value' => 'circular', 'label' => 'Circular'],
            ]"
        />
        <label class="inline-flex items-center gap-2 text-sm">
            <input type="checkbox" wire:model="is_standing" class="rounded" />
            Sempre na Lista de Batismos (aviso permanente)
        </label>
        <div>
            <label class="text-xs font-semibold text-slate-500">Ordem no boletim</label>
            <input type="number" wire:model="sort_order" min="0" max="9999" class="mt-1 w-28 rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
        </div>
        <div class="grid gap-2 sm:grid-cols-2">
            <div>
                <label class="text-xs font-semibold text-slate-500">Válido de</label>
                <input type="date" wire:model="valid_from" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-500">Válido até</label>
                <input type="date" wire:model="valid_until" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
            </div>
        </div>
        <div class="flex gap-2">
            <button class="rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white">Guardar</button>
            <button type="button" wire:click="resetForm" class="rounded-xl border px-4 py-2 text-sm">Limpar</button>
        </div>
    </form>
    <ul class="divide-y rounded-xl border border-slate-200 dark:border-slate-800">
        @forelse ($notices as $n)
            <li class="flex justify-between gap-2 p-3 text-sm">
                <div>
                    <strong>{{ $n->title }}</strong>
                    <span class="text-slate-500">({{ $n->category }})</span>
                    @if ($n->is_standing)
                        <span class="ml-1 rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-amber-800 dark:bg-amber-950/50 dark:text-amber-200">Permanente</span>
                    @endif
                    <div class="text-xs text-slate-500">
                        @if ($n->is_standing)
                            Em todas as listas · ordem {{ $n->sort_order }}
                        @else
                            {{ $n->valid_from->format('d/m/Y') }} – {{ $n->valid_until->format('d/m/Y') }} · ordem {{ $n->sort_order }}
                        @endif
                    </div>
                </div>
                <button type="button" wire:click="edit({{ $n->id }})" class="underline">Editar</button>
            </li>
        @empty
            <li class="p-3 text-sm text-slate-500">Nenhum aviso.</li>
        @endforelse
    </ul>
</div>
