<div class="space-y-4">
    <div class="flex flex-wrap items-center gap-2">
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Pesquisar eventos…" class="w-full max-w-md rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
        <a href="{{ route('agenda.events.index') }}" wire:navigate class="text-sm font-semibold text-primary-600 hover:underline">Voltar</a>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <x-ui.card>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase dark:bg-slate-900">
                        <tr>
                            <th class="px-4 py-3">Evento</th>
                            <th class="px-4 py-3">Início</th>
                            <th class="px-4 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($events as $e)
                            <tr wire:key="wa-ev-{{ $e->id }}">
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ $e->title }}</div>
                                    <div class="text-xs text-slate-500">{{ $e->eventType?->name }} @if($e->regional) · {{ $e->regional->name }} @endif</div>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $e->starts_at->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3 text-right">
                                    <x-ui.button size="sm" type="button" wire:click="openEditor({{ $e->id }})">Gerar / editar</x-ui.button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-8 text-center text-slate-500">Sem eventos com WhatsApp ativo.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $events->links() }}</div>
        </x-ui.card>

        <x-ui.card>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div class="text-sm font-semibold text-slate-800 dark:text-slate-100">Editor</div>
                    @if ($selected_event_id)
                        <div class="text-xs text-slate-500">Evento #{{ $selected_event_id }}</div>
                    @endif
                </div>

                @if (! $show_editor)
                    <p class="text-sm text-slate-600 dark:text-slate-300">Selecione um evento para gerar o aviso.</p>
                @endif

                @if ($show_editor)
                    <div>
                        <label class="text-xs font-semibold text-slate-500">Template</label>
                        <select wire:model="selected_template_id" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950">
                            <option value="">—</option>
                            @foreach ($templates as $tpl)
                                <option value="{{ $tpl->id }}">{{ $tpl->name }}</option>
                            @endforeach
                        </select>
                        @error('selected_template_id')
                            <p class="text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                        <div class="mt-2 flex flex-wrap gap-2">
                            <x-ui.button size="sm" type="button" wire:click="generate">Gerar rascunho</x-ui.button>
                            @if ($active_notice_id)
                                <x-ui.button size="sm" type="button" variant="secondary" wire:click="saveDraft">Guardar rascunho</x-ui.button>
                                <x-ui.button size="sm" type="button" variant="secondary" wire:click="markSent">Marcar enviado</x-ui.button>
                            @else
                                <x-ui.button size="sm" type="button" variant="secondary" wire:click="saveDraft" disabled>Guardar rascunho</x-ui.button>
                                <x-ui.button size="sm" type="button" variant="secondary" wire:click="markSent" disabled>Marcar enviado</x-ui.button>
                            @endif
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-500">Texto</label>
                        <textarea wire:model="body_final" rows="14" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950"></textarea>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100"
                            x-data
                            @click="navigator.clipboard.writeText(@js($body_final))"
                        >Copiar</button>

                        @if ($this->waLink)
                            <a href="{{ $this->waLink }}" target="_blank" rel="noreferrer" class="inline-flex items-center rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-700">Abrir no WhatsApp</a>
                        @endif
                    </div>

                    <div class="border-t border-slate-100 pt-4 dark:border-slate-800">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Histórico (últimos 10)</div>
                        <div class="mt-2 space-y-2">
                            @forelse ($history as $h)
                                <div class="rounded-xl border border-slate-200 p-3 text-xs dark:border-slate-800">
                                    <div class="flex items-center justify-between">
                                        <div class="font-semibold">{{ $h->status }}</div>
                                        <div class="text-slate-500">{{ $h->created_at->format('d/m/Y H:i') }}</div>
                                    </div>
                                    <div class="mt-2 whitespace-pre-wrap text-slate-700 dark:text-slate-200">{{ \Illuminate\Support\Str::limit($h->body_final, 240) }}</div>
                                </div>
                            @empty
                                <p class="text-xs text-slate-500">Sem histórico para este evento.</p>
                            @endforelse
                        </div>
                    </div>
                @endif
            </div>
        </x-ui.card>
    </div>
</div>

