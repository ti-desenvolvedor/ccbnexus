@if ($rows === [])
    <p class="mt-4 text-sm text-slate-600 dark:text-slate-300">Nenhum membro cadastrado neste recorte.</p>
@else
    <ul class="sr-only">
        @foreach ($rows as $row)
            <li>{{ $row['label'] }}: {{ $row['value'] }} {{ $row['value'] === 1 ? 'pessoa' : 'pessoas' }}</li>
        @endforeach
    </ul>
    <ul class="mt-4 space-y-3" aria-hidden="true">
        @foreach ($rows as $row)
            <li>
                <div class="flex items-baseline justify-between gap-3 text-sm">
                    <span class="min-w-0 truncate font-medium text-slate-800 dark:text-slate-100">{{ $row['label'] }}</span>
                    <span class="shrink-0 tabular-nums text-slate-700 dark:text-slate-200">{{ $row['value'] }}</span>
                </div>
                <div class="mt-1 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-900">
                    <div class="h-full rounded-full bg-primary-600" style="width: {{ $row['percent'] }}%"></div>
                </div>
            </li>
        @endforeach
    </ul>
    @if ($remainder > 0)
        <p class="mt-3 text-xs text-slate-600 dark:text-slate-300">Mais {{ $remainder }} {{ $remainder === 1 ? 'função' : 'funções' }} no cadastro.</p>
    @endif
@endif
