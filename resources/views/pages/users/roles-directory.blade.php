<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-primary-700 dark:text-primary-300">Utilizadores</div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">Papéis e permissões</h1>
            </div>
            <a href="{{ route('users.index') }}" wire:navigate>
                <x-ui.button variant="secondary">Voltar à lista</x-ui.button>
            </a>
        </div>
    </x-slot>
    <livewire:users.role-directory />
</x-app-layout>
