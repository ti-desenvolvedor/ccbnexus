<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">{{ $user ? 'Editar usuário' : 'Novo usuário' }}</h1>
        </div>
    </x-slot>
    @if ($user)
        @can('update', $user)
            <livewire:users.user-form :user="$user" :key="'user-form-'.$user->id" />
        @else
            <p class="text-sm text-rose-600">Sem permissão para editar este utilizador.</p>
        @endcan
    @else
        @can('create', \App\Models\User::class)
            <livewire:users.user-form :key="'user-form-new'" />
        @else
            <p class="text-sm text-rose-600">Sem permissão para criar utilizadores.</p>
        @endcan
    @endif
</x-app-layout>
