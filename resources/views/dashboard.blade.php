<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
            <div class="min-w-0 flex-1">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">{{ __('Esta semana') }}</h1>
                <p class="mt-1 max-w-2xl text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    {{ __('Cultos, cadastros, listas e o que ainda pede decisão. O restante da organização fica no menu.') }}
                </p>
            </div>
            @can('create', App\Models\Event::class)
                <x-ui.button href="{{ route('agenda.events.create') }}" class="min-h-11 w-full shrink-0 whitespace-normal text-center leading-snug xl:w-auto">{{ __('Novo evento') }}</x-ui.button>
            @endcan
        </div>
    </x-slot>

    <livewire:dashboard.metrics />
</x-app-layout>
