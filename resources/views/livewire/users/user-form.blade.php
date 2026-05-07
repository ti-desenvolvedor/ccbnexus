<x-ui.card title="{{ $user ? 'Editar utilizador' : 'Novo utilizador' }}">
    <form wire:submit="save" class="grid max-w-2xl gap-4">
        <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Nome</label>
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
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Regional</label>
            <select wire:model.live="regional_id" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950">
                <option value="">—</option>
                @foreach ($regionals as $r)
                    <option value="{{ $r->id }}">{{ $r->name }}</option>
                @endforeach
            </select>
            @error('regional_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Administração</label>
            <select wire:model.live="administration_id" @disabled(! $regional_id) class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm disabled:opacity-60 dark:border-slate-800 dark:bg-slate-950">
                <option value="">—</option>
                @foreach ($administrations as $a)
                    <option value="{{ $a->id }}">{{ $a->name }}</option>
                @endforeach
            </select>
            @error('administration_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Casa de oração</label>
            <select wire:model="prayer_house_id" @disabled(! $administration_id) class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm disabled:opacity-60 dark:border-slate-800 dark:bg-slate-950">
                <option value="">—</option>
                @foreach ($prayerHouses as $h)
                    <option value="{{ $h->id }}">{{ $h->name }}</option>
                @endforeach
            </select>
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
            @error('password') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Confirmar password</label>
            <input type="password" wire:model="password_confirmation" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" autocomplete="new-password" />
        </div>

        <div class="flex flex-wrap gap-2">
            <x-ui.button type="submit">Guardar</x-ui.button>
            <a href="{{ route('users.index') }}" wire:navigate class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold dark:border-slate-800">Cancelar</a>
        </div>
    </form>
</x-ui.card>
