<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="text-xs font-semibold uppercase tracking-wide text-primary-700 dark:text-primary-300">Organização</div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">
                {{ $regional ? 'Editar regional' : 'Nova regional' }}
            </h1>
        </div>
    </x-slot>
    <livewire:organization.regional-form :regional="$regional" />
</x-app-layout>
