<div class="space-y-4">
    <h1 class="text-xl font-semibold">Grupos WhatsApp</h1>
    <p class="text-sm text-slate-600">Destinos para eventos (incluindo os que não entram na Lista de Batismos).</p>
    @if (session('status')) <x-nexus.alert tone="success">{{ session('status') }}</x-nexus.alert> @endif
    <form wire:submit="save" class="grid w-full min-w-0 gap-3 rounded-xl border p-4 md:grid-cols-2 dark:border-slate-800">
        <input type="text" wire:model="name" placeholder="Nome do grupo" class="rounded-xl border px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
        <input type="text" wire:model="invite_hint" placeholder="Dica / link (opcional)" class="rounded-xl border px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
        <button class="min-h-11 rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white md:col-span-2 md:w-fit">Guardar</button>
    </form>
    <ul class="divide-y rounded-xl border dark:border-slate-800">
        @foreach ($groups as $g)
            <li class="p-3 text-sm">{{ $g->name }} @if($g->invite_hint)<span class="text-slate-500">— {{ $g->invite_hint }}</span>@endif</li>
        @endforeach
    </ul>
</div>
