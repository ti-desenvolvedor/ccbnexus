<div class="space-y-4">
    <p class="text-sm text-slate-600 dark:text-slate-300">Referência dos papéis e permissões definidos no sistema (somente leitura). Alterações ao catálogo são feitas por migrações/seeders.</p>
    <div class="space-y-3">
        @foreach ($roles as $role)
            <x-ui.card>
                <div class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $role->name }}</div>
                <p class="mt-2 text-xs text-slate-500">{{ $role->permissions->count() }} permissões</p>
                <ul class="mt-2 max-h-40 list-inside list-disc overflow-y-auto text-xs text-slate-600 dark:text-slate-300">
                    @foreach ($role->permissions->sortBy('name') as $p)
                        <li>{{ $p->name }}</li>
                    @endforeach
                </ul>
            </x-ui.card>
        @endforeach
    </div>
</div>
