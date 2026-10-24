<div class="grid gap-6 lg:grid-cols-3">
    <x-ui.card class="lg:col-span-1">
        <div class="flex items-center justify-between gap-2">
            <label class="text-xs font-semibold text-slate-500">Buscar tipo</label>
            <button type="button" wire:click="startCreate" class="text-xs font-semibold text-primary-700 hover:underline dark:text-primary-300">Novo tipo</button>
        </div>
        <input type="search" wire:model.live.debounce.300ms="search" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" placeholder="RRM, batismo, organistas…" />
        <ul class="mt-3 max-h-[32rem] space-y-1 overflow-y-auto">
            @forelse ($types as $type)
                <li>
                    <button type="button" wire:click="edit({{ $type->id }})" @class([
                        'w-full rounded-lg px-3 py-2 text-left text-sm',
                        'bg-primary-600 text-white' => ! $creating && $editing_id === $type->id,
                        'hover:bg-slate-100 dark:hover:bg-slate-900' => $creating || $editing_id !== $type->id,
                    ])>
                        <span class="block font-semibold">{{ $type->name }}</span>
                        <span class="block text-xs opacity-70">{{ $type->audiencePreset?->report_layout ?: 'sem modelo' }}</span>
                    </button>
                </li>
            @empty
                <li class="px-2 py-4 text-sm text-slate-500">Nenhum tipo encontrado.</li>
            @endforelse
        </ul>
    </x-ui.card>

    <x-ui.card class="lg:col-span-2">
        @if (! $editing_id && ! $creating)
            <p class="text-sm text-slate-600 dark:text-slate-300">Escolha um tipo à esquerda ou clique em Novo tipo. O nome aparece na agenda. O texto do modelo entra na lista de batismos.</p>
        @else
            <form wire:submit="save" data-progress="marked" class="space-y-4">
                <x-nexus.form-progress />
                <div>
                    <label class="text-xs font-semibold text-slate-500" for="event-type-name">Nome do tipo</label>
                    <input id="event-type-name" type="text" wire:model="name" data-progress-field class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" placeholder="Ex.: Reunião de mocidade" />
                    @error('name') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-500">Como a lista mostra este tipo</label>
                    <x-nexus.searchable-select
                        wire:model="report_layout"
                        data-progress-field
                        class="mt-1"
                        search-placeholder="Buscar modelo..."
                        :options="collect($layouts)->map(fn ($label, $value) => ['value' => (string) $value, 'label' => (string) $label])"
                    />
                </div>
                <div wire:key="paragraph-editor-{{ $editing_id }}">
                    <label class="text-xs font-semibold text-slate-500">Texto do modelo</label>
                    <p class="mt-1 text-xs text-slate-500">Negrito, itálico, sublinhado e marcadores. O botão Inserir campo coloca data, hora, local e os outros dados do evento no cursor.</p>
                    <div class="mt-2">
                        <x-ui.simple-editor model="paragraph" :value="$paragraph" :fields="$fields" live placeholder="Escreva o texto do modelo…" body-class="min-h-56" />
                    </div>
                    @error('paragraph') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
                </div>
                @if ($preview !== '')
                    <div>
                        <div class="text-xs font-semibold text-slate-500">Pré-visualização com data de exemplo</div>
                        <div class="mt-2 rounded-xl bg-slate-50 p-3 text-sm text-slate-800 dark:bg-slate-950 dark:text-slate-100 [&_b]:font-bold [&_em]:italic [&_i]:italic [&_ol]:list-decimal [&_ol]:pl-5 [&_strong]:font-bold [&_u]:underline [&_ul]:my-1 [&_ul]:list-disc [&_ul]:pl-5">{!! \App\Support\SimpleRichText::toHtml($preview) !!}</div>
                    </div>
                @endif
                @if (session('status'))
                    <x-nexus.alert tone="success">{{ session('status') }}</x-nexus.alert>
                @endif
                <x-ui.button type="submit">{{ $creating ? 'Criar tipo' : 'Guardar tipo' }}</x-ui.button>
            </form>
        @endif
    </x-ui.card>
</div>
