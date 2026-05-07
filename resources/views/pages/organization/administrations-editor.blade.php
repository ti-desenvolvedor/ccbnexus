<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="text-xs font-semibold uppercase tracking-wide text-primary-700 dark:text-primary-300">Organização</div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">
                {{ $administration ? 'Editar administração' : 'Nova administração' }}
            </h1>
        </div>
    </x-slot>
    <livewire:organization.administration-form :administration="$administration" />
</x-app-layout>
