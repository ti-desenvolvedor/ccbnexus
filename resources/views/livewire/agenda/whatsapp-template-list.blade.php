<div class="space-y-4">
    <div class="flex flex-wrap items-center gap-2">
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Pesquisar templates…" class="w-full max-w-md rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
        @can('create', \App\Models\WhatsAppNoticeTemplate::class)
            <x-ui.button type="button" wire:click="create">Novo template</x-ui.button>
        @endcan
        <a href="{{ route('agenda.whatsapp.index') }}" wire:navigate class="text-sm font-semibold text-primary-600 hover:underline">Central</a>
    </div>

    @if ($show_form)
        <x-ui.card>
            <form wire:submit="save" class="grid gap-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-xs font-semibold text-slate-500">Nome</label>
                        <input type="text" wire:model.live="name" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
                        @error('name') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-500">Slug</label>
                        <input type="text" wire:model="slug" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
                        @error('slug') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-500">Corpo</label>
                    <textarea wire:model="body" rows="10" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950"></textarea>
                    <p class="mt-1 text-xs text-slate-500">Placeholders: {event_title}, {date}, {time}, {weekday}, {location}, {location_hybrid}, {dress_code}, {audience_text}, {notes}, {link}, {signature}</p>
                    @error('body') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex flex-wrap gap-6">
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="checkbox" wire:model="is_active" class="rounded" />
                        <span>Ativo</span>
                    </label>
                    @can('setDefault', \App\Models\WhatsAppNoticeTemplate::class)
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="checkbox" wire:model="is_default" class="rounded" />
                            <span>Marcar como padrão</span>
                        </label>
                    @endcan
                </div>

                <div class="flex flex-wrap gap-2 border-t border-slate-100 pt-4 dark:border-slate-800">
                    <x-ui.button type="submit">Guardar</x-ui.button>
                    <x-ui.button type="button" variant="secondary" wire:click="$set('show_form', false)">Cancelar</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    @endif

    <x-ui.card>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase dark:bg-slate-900">
                    <tr>
                        <th class="px-4 py-3">Nome</th>
                        <th class="px-4 py-3">Slug</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($templates as $t)
                        <tr wire:key="wa-tpl-{{ $t->id }}">
                            <td class="px-4 py-3 font-medium">
                                {{ $t->name }}
                                @if ($t->is_default)
                                    <span class="ml-2 rounded-full bg-primary-100 px-2 py-0.5 text-[10px] font-semibold text-primary-800 dark:bg-primary-950/40 dark:text-primary-200">Padrão</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $t->slug }}</td>
                            <td class="px-4 py-3">{{ $t->is_active ? 'Ativo' : 'Inativo' }}</td>
                            <td class="px-4 py-3 text-right space-x-3">
                                @can('update', $t)
                                    <button type="button" wire:click="edit({{ $t->id }})" class="text-primary-600 hover:underline">Editar</button>
                                @endcan
                                @can('setDefault', \App\Models\WhatsAppNoticeTemplate::class)
                                    @if (! $t->is_default)
                                        <button type="button" wire:click="setDefault({{ $t->id }})" class="text-primary-600 hover:underline">Tornar padrão</button>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">Sem templates.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $templates->links() }}</div>
    </x-ui.card>
</div>

