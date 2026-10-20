<x-ui.card title="{{ $user ? 'Editar utilizador' : 'Novo utilizador' }}">
    <form wire:submit="save" class="grid max-w-2xl gap-4">
        <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('Nome') }}</label>
            <input type="text" wire:model="name" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
            @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Email</label>
            <input type="email" wire:model="email" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" autocomplete="username" />
            @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Telefone</label>
            <input type="tel" wire:model="phone" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
            @error('phone') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Âmbito organizacional</p>
        <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('Regional') }}</label>
            <x-nexus.searchable-select wire:model.live="regional_id" class="mt-1" empty-label="—" search-placeholder="Buscar regional..." :options="$regionals" />
            @error('regional_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('Administração') }}</label>
            <x-nexus.searchable-select wire:model.live="administration_id" class="mt-1" empty-label="—" search-placeholder="Buscar administração..." :options="$administrations" :disabled="! $regional_id" />
            @error('administration_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('Casa de oração') }}</label>
            <x-nexus.searchable-select wire:model="prayer_house_id" class="mt-1" empty-label="—" search-placeholder="Buscar casa..." label-key="display_label" :options="$prayerHouses" :disabled="! $administration_id" />
            @error('prayer_house_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        @if (auth()->user()->isSuperAdmin())
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model="is_super_admin" class="rounded" />
                Super-administrador
            </label>
        @endif

        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Papéis (Spatie)</p>
        <div class="grid gap-2 sm:grid-cols-2">
            @foreach ($roles as $role)
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" wire:model="role_names" value="{{ $role->name }}" class="rounded" />
                    {{ $role->name }}
                </label>
            @endforeach
        </div>
        @error('role_names') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror

        <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">{{ $user ? 'Nova password (opcional)' : 'Password' }}</label>
            <input type="password" wire:model="password" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" autocomplete="new-password" />
            <p class="mt-1 text-xs text-slate-500">{{ __('auth.password_rules') }}</p>
            @error('password') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Confirmar password</label>
            <input type="password" wire:model="password_confirmation" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" autocomplete="new-password" />
        </div>

        <div class="flex flex-wrap gap-2">
            <x-ui.button type="submit">{{ __('Guardar') }}</x-ui.button>
            <a href="{{ route('users.index') }}" wire:navigate class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold dark:border-slate-800">{{ __('Cancelar') }}</a>
        </div>
    </form>
</x-ui.card>
