<div class="space-y-4">
    <h1 class="text-xl font-semibold">Coletas especiais</h1>
    <p class="text-sm text-slate-600">Programadas ou urgentes (reunião). Entram na Lista de Batismos, não são um relatório separado.</p>
    @if (session('status')) <x-nexus.alert tone="success">{{ session('status') }}</x-nexus.alert> @endif
    <form wire:submit="save" data-progress="marked" class="grid w-full min-w-0 gap-3 rounded-xl border p-4 md:grid-cols-2 dark:border-slate-800">
        <x-nexus.form-progress />
        <x-nexus.searchable-select
            data-progress-field
            wire:model="mode"
            search-placeholder="Buscar modo..."
            :options="[
                ['value' => 'programada', 'label' => 'Programada'],
                ['value' => 'urgente', 'label' => 'Sem agendamento (urgente)'],
            ]"
        />
        <input type="text" wire:model="title" data-progress-field placeholder="Título" class="rounded-xl border px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
        <textarea wire:model="body" data-progress-field rows="3" class="rounded-xl border px-3 py-2 text-sm md:col-span-2 dark:border-slate-800 dark:bg-slate-950"></textarea>
        <div class="grid gap-2 md:col-span-2 lg:grid-cols-3">
            <input type="date" wire:model="collection_on" aria-label="Data da coleta" class="rounded-xl border px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
            <input type="date" wire:model="valid_from" data-progress-field aria-label="Válido de" class="rounded-xl border px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
            <input type="date" wire:model="valid_until" data-progress-field aria-label="Válido até" class="rounded-xl border px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
        </div>
        <button class="min-h-11 rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white md:col-span-2 md:w-fit">Guardar</button>
    </form>
    <ul class="divide-y rounded-xl border dark:border-slate-800">
        @foreach ($rows as $r)
            <li class="p-3 text-sm"><strong>{{ $r->title }}</strong> · {{ $r->mode }}</li>
        @endforeach
    </ul>
</div>
