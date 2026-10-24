<div class="min-w-0 space-y-8">
    <header class="max-w-[68ch] space-y-2">
        <h1 class="text-balance text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-50">Quem atende</h1>
        <p class="text-sm leading-6 text-slate-700 dark:text-slate-300">
            Pessoas do ministério desta regional. A comum é a congregação da pessoa.
            As igrejas de atendimento podem ser várias, iguais ou diferentes da comum.
            O celular é opcional e este cadastro não marca um evento.
        </p>
    </header>

    @if (session('servant_status'))
        <x-nexus.inline-message tone="success" class="font-semibold">{{ session('servant_status') }}</x-nexus.inline-message>
    @endif

    @if (! $canRegisterServant)
        <p class="text-sm text-slate-700 dark:text-slate-300">Selecione uma regional no contexto para cadastrar quem atende.</p>
    @elseif ($ministryOffices->isEmpty())
        <p class="text-sm text-slate-700 dark:text-slate-300">Esta regional ainda não tem cargos de ministério no catálogo. Cadastre Ancião, Diácono e os demais em Ministério antes de incluir pessoas.</p>
    @else
        <form wire:submit="save" data-progress="marked" class="space-y-5 rounded-xl border border-slate-200 p-6 dark:border-slate-800">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-50">{{ $editingId ? 'Editar cadastro' : 'Novo cadastro' }}</h2>
            <x-nexus.form-progress />

            <div class="grid gap-3 sm:grid-cols-2 lg:gap-4">
                <label for="servant-name" class="block text-sm font-semibold text-slate-800 dark:text-slate-100">
                    Nome completo
                    <input id="servant-name" type="text" wire:model="servant_name" data-progress-field autocomplete="name" class="mt-1 block min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-normal text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100" />
                    @error('servant_name') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
                </label>
                <label for="servant-position" class="block text-sm font-semibold text-slate-800 dark:text-slate-100">
                    Ministério
                    <x-nexus.searchable-select id="servant-position" wire:model="servant_position_id" data-progress-field class="mt-1" empty-label="Escolher cargo…" search-placeholder="Buscar cargo..." :options="$ministryOffices" />
                    @error('servant_position_id') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
                </label>
                <label for="servant-phone" class="block text-sm font-semibold text-slate-800 dark:text-slate-100">
                    Celular com DDD (opcional)
                    <input id="servant-phone" type="tel" wire:model="servant_phone" inputmode="tel" placeholder="11 98765-4321" class="mt-1 block min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-normal tabular-nums text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100" />
                    @error('servant_phone') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
                </label>
                <label for="servant-ordained-on" class="block text-sm font-semibold text-slate-800 dark:text-slate-100">
                    Data de ordenação (opcional)
                    <span class="mt-1 block text-sm font-normal text-slate-700 dark:text-slate-300">Data em que a pessoa assumiu o cargo.</span>
                    <input id="servant-ordained-on" type="date" wire:model="ordained_on" class="mt-1 block min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-normal tabular-nums text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100" />
                    @error('ordained_on') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
                </label>
                <label for="servant-comum" class="block text-sm font-semibold text-slate-800 dark:text-slate-100">
                    Comum
                    <x-nexus.searchable-select id="servant-comum" wire:model="comum_prayer_house_id" class="mt-1" empty-label="Sem comum" search-placeholder="Buscar casa..." label-key="display_label" :options="$prayerHouses" />
                    @error('comum_prayer_house_id') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
                </label>
            </div>

            <label for="servant-attendance" class="block text-sm font-semibold text-slate-800 dark:text-slate-100">
                Igrejas de atendimento
                <span class="mt-1 block text-sm font-normal text-slate-700 dark:text-slate-300">Pode escolher mais de uma. A comum também pode estar nesta lista.</span>
                <x-nexus.searchable-select id="servant-attendance" multiple wire:model="attendance_prayer_house_ids" class="mt-1" placeholder="Escolher casas..." search-placeholder="Buscar casa..." label-key="display_label" :options="$prayerHouses" />
                @error('attendance_prayer_house_ids') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
            </label>

            <div class="flex flex-wrap justify-end gap-2">
                @if ($editingId)
                    <button type="button" wire:click="cancel" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100">Cancelar</button>
                @endif
                <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-primary-700 px-4 text-sm font-semibold text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">{{ $editingId ? 'Guardar' : 'Cadastrar' }}</button>
            </div>
        </form>
    @endif

    <div class="grid grid-cols-1">
        <div class="space-y-3 md:hidden">
            @forelse ($servants as $servant)
                <article wire:key="servant-card-{{ $servant->id }}" class="space-y-1 rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                    <div class="flex items-start justify-between gap-3">
                        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ $servant->full_name }}</h2>
                        @if ($canRegisterServant)
                            <button type="button" wire:click="edit({{ $servant->id }})" class="inline-flex min-h-11 shrink-0 items-center font-semibold text-primary-700 underline decoration-primary-700/40 underline-offset-4 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:text-primary-300">Editar</button>
                        @endif
                    </div>
                    <p class="text-sm text-slate-700 dark:text-slate-300">{{ $servant->ministry }} · {{ $servant->ordained_on?->format('d/m/Y') ?? 'Sem data de ordenação' }}</p>
                    <p class="text-sm text-slate-700 dark:text-slate-300">Comum: {{ $servant->comum?->display_label ?? '—' }}</p>
                    <p class="text-sm text-slate-700 dark:text-slate-300">Atendimento: {{ $servant->attendanceHouses->isEmpty() ? '—' : $servant->attendanceHouses->pluck('display_label')->join(', ') }}</p>
                </article>
            @empty
                <p class="rounded-xl border border-slate-200 px-4 py-10 text-center text-sm text-slate-700 dark:border-slate-800 dark:text-slate-300">Nenhuma pessoa cadastrada nesta regional.</p>
            @endforelse
        </div>
        <div class="hidden overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800 md:block">
            <table class="w-max min-w-full border-separate border-spacing-0 text-sm">
                <thead>
                    <tr class="bg-slate-100 text-left text-slate-800 dark:bg-slate-900 dark:text-slate-100">
                        <th scope="col" class="px-4 py-3 font-semibold">Nome</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Ministério</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Data de ordenação</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Comum</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Igrejas de atendimento</th>
                        <th scope="col" class="px-4 py-3 font-semibold"><span class="sr-only">Ações</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($servants as $servant)
                        <tr wire:key="servant-{{ $servant->id }}" class="{{ $loop->even ? 'bg-slate-100 dark:bg-slate-900' : 'bg-white dark:bg-slate-950' }}">
                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-900 dark:text-slate-100">{{ $servant->full_name }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-800 dark:text-slate-200">{{ $servant->ministry }}</td>
                            <td class="whitespace-nowrap px-4 py-3 tabular-nums text-slate-800 dark:text-slate-200">{{ $servant->ordained_on?->format('d/m/Y') ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-800 dark:text-slate-200">{{ $servant->comum?->display_label ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-800 dark:text-slate-200">
                                {{ $servant->attendanceHouses->isEmpty() ? '—' : $servant->attendanceHouses->pluck('display_label')->join(', ') }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if ($canRegisterServant)
                                    <button type="button" wire:click="edit({{ $servant->id }})" class="inline-flex min-h-11 items-center font-semibold text-primary-700 underline decoration-primary-700/40 underline-offset-4 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:text-primary-300">Editar</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr class="bg-white dark:bg-slate-950">
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-700 dark:text-slate-300">Nenhuma pessoa cadastrada nesta regional.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
