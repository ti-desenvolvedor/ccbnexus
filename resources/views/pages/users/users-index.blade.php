<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-primary-700 dark:text-primary-300">Utilizadores</div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">Gestão de utilizadores</h1>
            </div>
            <div class="flex flex-wrap gap-2">
                @can('viewUserRoleDirectory')
                    <a href="{{ route('users.roles') }}" wire:navigate>
                        <x-ui.button variant="secondary">Papéis e permissões</x-ui.button>
                    </a>
                @endcan
                @can('create', \App\Models\User::class)
                    <a href="{{ route('users.create') }}" wire:navigate>
                        <x-ui.button>Novo utilizador</x-ui.button>
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>
    <livewire:users.user-list />
</x-app-layout>
