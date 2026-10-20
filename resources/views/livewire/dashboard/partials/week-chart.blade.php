@php
    $hasEvents = collect($weeks)->contains(fn (array $week) => $week['value'] > 0);
@endphp

@if (! $hasEvents)
    <p class="mt-4 text-sm text-slate-600 dark:text-slate-300">Nenhum evento nestas oito semanas.</p>
@else
    <ul class="sr-only">
        @foreach ($weeks as $week)
            <li>{{ $week['label'] }}: {{ $week['value'] }} {{ $week['value'] === 1 ? 'evento' : 'eventos' }}</li>
        @endforeach
    </ul>
    <div class="mt-4 flex h-40 items-end gap-2" aria-hidden="true">
        @foreach ($weeks as $week)
            <div class="flex min-w-0 flex-1 flex-col items-center gap-1">
                <span class="text-xs tabular-nums text-slate-700 dark:text-slate-200">{{ $week['value'] }}</span>
                <div class="flex h-24 w-full items-end rounded-md bg-slate-100 dark:bg-slate-900">
                    <div
                        class="w-full rounded-md {{ $week['value'] > 0 ? 'bg-primary-600' : 'bg-transparent' }}"
                        style="height: {{ $week['percent'] }}%"
                    ></div>
                </div>
                <span class="text-xs tabular-nums text-slate-600 dark:text-slate-300">{{ $week['label'] }}</span>
            </div>
        @endforeach
    </div>
    <dl class="mt-4 grid grid-cols-3 gap-2 text-center text-sm">
        <div class="rounded-xl bg-emerald-50 px-2 py-2 text-emerald-950 dark:bg-emerald-950 dark:text-emerald-100">
            <dt class="text-xs font-medium">Publicados</dt>
            <dd class="mt-1 text-lg font-semibold tabular-nums">{{ $status['published'] }}</dd>
        </div>
        <div class="rounded-xl bg-amber-50 px-2 py-2 text-amber-950 dark:bg-amber-950 dark:text-amber-100">
            <dt class="text-xs font-medium">Pendentes</dt>
            <dd class="mt-1 text-lg font-semibold tabular-nums">{{ $status['pending'] }}</dd>
        </div>
        <div class="rounded-xl bg-slate-100 px-2 py-2 text-slate-900 dark:bg-slate-900 dark:text-slate-100">
            <dt class="text-xs font-medium">Rascunhos</dt>
            <dd class="mt-1 text-lg font-semibold tabular-nums">{{ $status['draft'] }}</dd>
        </div>
    </dl>
@endif
