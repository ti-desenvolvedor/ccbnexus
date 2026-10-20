<div>
    <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $title }}</h3>
    <div class="nexus-scroll mt-2 max-h-64 overflow-y-auto rounded-2xl border border-slate-200/80 bg-white px-4 dark:border-slate-800 dark:bg-slate-950">
        @if ($reports === [])
            <p class="py-5 text-sm text-slate-600 dark:text-slate-300">{{ $empty }}</p>
        @else
            <ul class="divide-y divide-slate-200 dark:divide-slate-800">
                @foreach ($reports as $report)
                    <li wire:key="list-{{ $title }}-{{ $report['id'] }}" class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <a href="{{ $report['href'] }}" wire:navigate class="font-semibold text-slate-900 underline-offset-2 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 dark:text-slate-100">
                                {{ $report['issue'] }} · {{ $report['revision'] }}
                            </a>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                                Período {{ $report['period'] }} · {{ $report['itemsLabel'] }} · {{ $report['when'] }}
                            </p>
                        </div>
                        <x-ui.badge :tone="$report['tone']">{{ $report['status'] }}</x-ui.badge>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
