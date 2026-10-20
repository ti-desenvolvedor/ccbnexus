<div class="space-y-4">
    <h1 class="text-xl font-semibold">Coletas especiais</h1>
    <p class="text-sm text-slate-600">Programadas ou urgentes (reunião). Entram na Lista de Batismos, não são um relatório separado.</p>
    @if (session('status')) <p class="text-sm text-emerald-700">{{ session('status') }}</p> @endif
    <form wire:submit="save" class="grid gap-3 rounded-xl border p-4 dark:border-slate-800">
        <x-nexus.searchable-select
            wire:model="mode"
            search-placeholder="Buscar modo..."
            :options="[
                ['value' => 'programada', 'label' => 'Programada'],
                ['value' => 'urgente', 'label' => 'Sem agendamento (urgente)'],
            ]"
        />
        <input type="text" wire:model="title" placeholder="Título" class="rounded-xl border px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
        <textarea wire:model="body" rows="3" class="rounded-xl border px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950"></textarea>
        <input type="date" wire:model="collection_on" class="rounded-xl border px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
        <div class="grid gap-2 sm:grid-cols-2">
            <input type="date" wire:model="valid_from" class="rounded-xl border px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
            <input type="date" wire:model="valid_until" class="rounded-xl border px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
        </div>
        <button class="rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white">Guardar</button>
    </form>
    <ul class="divide-y rounded-xl border dark:border-slate-800">
        @foreach ($rows as $r)
            <li class="p-3 text-sm"><strong>{{ $r->title }}</strong> · {{ $r->mode }}</li>
        @endforeach
    </ul>
</div>
