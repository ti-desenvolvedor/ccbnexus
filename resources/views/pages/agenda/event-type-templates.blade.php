<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="text-xs font-semibold uppercase tracking-wide text-primary-700 dark:text-primary-300">{{ __('Agenda') }}</div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">Modelos dos tipos de evento</h1>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Cada tipo tem um texto. A lista de batismos preenche data, hora e local a partir do evento.</p>
        </div>
    </x-slot>

    <livewire:agenda.event-type-template-manager />
</x-app-layout>
