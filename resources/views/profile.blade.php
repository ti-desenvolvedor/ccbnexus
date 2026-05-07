<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="text-xs font-semibold uppercase tracking-wide text-primary-700 dark:text-primary-300">Conta</div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">{{ __('Profile') }}</h1>
        </div>
    </x-slot>

    <div class="space-y-6">
        <x-ui.card>
            <div class="max-w-xl">
                <livewire:profile.update-profile-information-form />
            </div>
        </x-ui.card>

        <x-ui.card>
            <div class="max-w-xl">
                <livewire:profile.update-password-form />
            </div>
        </x-ui.card>

        <x-ui.card>
            <div class="max-w-xl">
                <livewire:profile.delete-user-form />
            </div>
        </x-ui.card>
    </div>
</x-app-layout>
