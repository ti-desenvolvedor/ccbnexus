<li wire:key="event-{{ $event['id'] }}" class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between">
    <div class="min-w-0">
        <a href="{{ $event['href'] }}" wire:navigate class="font-semibold text-slate-900 underline-offset-2 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 dark:text-slate-100">{{ $event['title'] }}</a>
        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
            {{ $event['when'] }}
            @if ($event['place'])
                · {{ $event['place'] }}
            @endif
            @if ($event['type'])
                · {{ $event['type'] }}
            @endif
        </p>
        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
            @if ($event['attendant'])
                Quem atende: {{ $event['attendant'] }}
            @else
                Sem quem atende
            @endif
        </p>
    </div>
    @if ($event['showStatus'] ?? true)
        <x-ui.badge :tone="$event['tone']">{{ $event['status'] }}</x-ui.badge>
    @endif
</li>
