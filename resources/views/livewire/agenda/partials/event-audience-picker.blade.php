@php
    $administrative = $audiencePicker['administrative'] ?? collect();
    $operational = $audiencePicker['operational'] ?? collect();
    $ministry = $audiencePicker['ministry'] ?? collect();
    $music = $audiencePicker['music'] ?? collect();
    $selectedCount = count($public_position_ids);
    $hasAny = $administrative->isNotEmpty() || $operational->isNotEmpty() || $ministry->isNotEmpty() || $music->isNotEmpty();
@endphp

<div class="space-y-4">
    <div class="flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/40">
        <div class="min-w-0 flex-1">
            <p class="text-sm text-slate-600 dark:text-slate-300">
                {{ __('Selecione os cargos do público-alvo: Grupos I–V, serviços operacionais, Ministério e Música.') }}
            </p>
            @if ($selectedCount > 0)
                <p class="mt-1 text-xs font-medium text-primary-700 dark:text-primary-300">
                    {{ $selectedCount }} {{ $selectedCount === 1 ? __('cargo selecionado') : __('cargos selecionados') }}
                </p>
            @endif
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs font-semibold text-slate-500">{{ __('Âmbito') }}</span>
            <button
                type="button"
                wire:click="$set('publicAudienceScope', '{{ \App\Models\PublicDepartment::SCOPE_REGIONAL }}')"
                class="inline-flex min-h-11 items-center rounded-lg px-3 text-sm font-semibold {{ $publicAudienceScope === \App\Models\PublicDepartment::SCOPE_REGIONAL ? 'bg-primary-600 text-white' : 'bg-white text-slate-700 ring-1 ring-slate-200 dark:bg-slate-950 dark:text-slate-300 dark:ring-slate-700' }}"
            >{{ __('Regional') }}</button>
            <button
                type="button"
                wire:click="$set('publicAudienceScope', '{{ \App\Models\PublicDepartment::SCOPE_ADMINISTRATION }}')"
                class="inline-flex min-h-11 items-center rounded-lg px-3 text-sm font-semibold {{ $publicAudienceScope === \App\Models\PublicDepartment::SCOPE_ADMINISTRATION ? 'bg-primary-600 text-white' : 'bg-white text-slate-700 ring-1 ring-slate-200 dark:bg-slate-950 dark:text-slate-300 dark:ring-slate-700' }}"
            >{{ __('Administração') }}</button>
            @if ($selectedCount > 0)
                <button type="button" wire:click="clearPublicPositions" class="inline-flex min-h-11 items-center rounded-lg px-3 text-sm text-slate-600 hover:underline">
                    {{ __('Limpar seleção') }}
                </button>
            @endif
        </div>
    </div>

    @if (! $regional_id)
        <p class="text-sm text-amber-700 dark:text-amber-300">{{ __('Defina a regional em «Dados» para listar cargos.') }}</p>
    @elseif (! $hasAny)
        <p class="text-sm text-slate-500">
            {{ __('Nenhum cargo ativo para este âmbito.') }}
            <a href="{{ route('agenda.public-catalog.index') }}" wire:navigate class="text-primary-600 hover:underline">{{ __('Catálogo público') }}</a>
        </p>
    @else
        <div class="grid gap-8">
            @if ($administrative->isNotEmpty())
                <section>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ __('Estrutura administrativa (Grupos I–V)') }}</h3>
                    <div class="mt-3 space-y-3">
                        @foreach ($administrative as $block)
                            @php
                                $subgroup = $block['subgroup'];
                                $selectedIds = collect($public_position_ids)->map(fn ($id) => (int) $id);
                                $groupOpen = collect($block['departments'])
                                    ->flatMap(fn ($row) => $row['positions']->pluck('id'))
                                    ->map(fn ($id) => (int) $id)
                                    ->intersect($selectedIds)
                                    ->isNotEmpty();
                            @endphp
                            <details wire:key="audience-group-{{ $subgroup->id }}" wire:ignore.self class="rounded-xl border border-slate-200 dark:border-slate-800" @if ($groupOpen) open @endif>
                                <summary class="flex min-h-11 cursor-pointer flex-wrap items-center justify-between gap-2 bg-slate-50 px-4 py-2 text-sm font-semibold dark:bg-slate-900/60">
                                    <span>{{ $subgroup->name }}</span>
                                    <button type="button" wire:click.stop="selectSubgroupPositions({{ $subgroup->id }})" class="inline-flex min-h-11 items-center text-sm font-semibold text-primary-700 hover:underline dark:text-primary-300">{{ __('Selecionar todos os cargos do grupo') }}</button>
                                </summary>
                                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                                    @foreach ($block['departments'] as $row)
                                        @php
                                            $dept = $row['department'];
                                            $positions = $row['positions'];
                                            $catalogNum = $dept->meta['catalog_number'] ?? null;
                                        @endphp
                                        <div class="px-4 py-3">
                                            <div class="flex flex-wrap items-start justify-between gap-2">
                                                <div>
                                                    <span class="text-xs text-slate-500">{{ $catalogNum ? str_pad((string) $catalogNum, 2, '0', STR_PAD_LEFT).' — ' : '' }}</span>
                                                    <span class="font-medium text-slate-900 dark:text-slate-100">{{ $dept->name }}</span>
                                                    <x-ui.badge tone="info" class="ml-2">{{ $dept->scope === \App\Models\PublicDepartment::SCOPE_REGIONAL ? __('Regional') : __('Administração') }}</x-ui.badge>
                                                </div>
                                                @if ($positions->isNotEmpty())
                                                    <button type="button" wire:click="selectDepartmentPositions({{ $dept->id }})" class="inline-flex min-h-11 items-center text-sm font-semibold text-primary-700 hover:underline dark:text-primary-300">{{ __('Selecionar departamento') }}</button>
                                                @endif
                                            </div>
                                            @if ($positions->isEmpty())
                                                <p class="mt-2 text-xs italic text-slate-500">{{ __('Sem cargos cadastrados neste departamento.') }}</p>
                                            @else
                                                <div class="mt-2 grid gap-1.5 sm:grid-cols-2">
                                                    @foreach ($positions as $p)
                                                        <label class="flex min-h-11 items-center gap-2 rounded-lg border border-transparent px-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-900/50">
                                                            <input type="checkbox" wire:model="public_position_ids" value="{{ $p->id }}" class="rounded" />
                                                            <span>{{ $p->name }}@if ($p->is_department_coordinator) <span class="text-xs text-slate-500">({{ __('Coord.') }})</span>@endif</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </details>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($operational->isNotEmpty())
                <section>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ __('Serviços operacionais unificados') }}</h3>
                    @include('livewire.agenda.partials.event-audience-axis-rows', ['rows' => $operational])
                </section>
            @endif

            @if ($ministry->isNotEmpty())
                <section>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ __('Ministério') }}</h3>
                    @include('livewire.agenda.partials.event-audience-axis-rows', ['rows' => $ministry])
                </section>
            @endif

            @if ($music->isNotEmpty())
                <section>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ __('Música') }}</h3>
                    <div class="mt-3 space-y-3">
                        @foreach ($music as $row)
                            @php
                                $dept = $row['department'];
                                $positions = $row['positions'];
                                $adminLabel = $row['label'] ?? null;
                            @endphp
                            <div class="rounded-xl border border-slate-200 px-4 py-3 dark:border-slate-800">
                                @if ($adminLabel)
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $adminLabel }}</p>
                                @endif
                                <div class="flex flex-wrap items-start justify-between gap-2 {{ $adminLabel ? 'mt-2' : '' }}">
                                    <span class="font-medium text-slate-900 dark:text-slate-100">{{ $dept->name }}</span>
                                    @if ($positions->isNotEmpty())
                                        <button type="button" wire:click="selectDepartmentPositions({{ $dept->id }})" class="inline-flex min-h-11 items-center text-sm font-semibold text-primary-700 hover:underline dark:text-primary-300">{{ __('Selecionar departamento') }}</button>
                                    @endif
                                </div>
                                @if ($positions->isEmpty())
                                    <p class="mt-2 text-xs italic text-slate-500">{{ __('Sem cargos cadastrados.') }}</p>
                                @else
                                    <div class="mt-2 grid gap-1.5 sm:grid-cols-2">
                                        @foreach ($positions as $p)
                                            <label class="flex min-h-11 items-center gap-2 text-sm">
                                                <input type="checkbox" wire:model="public_position_ids" value="{{ $p->id }}" class="rounded" />
                                                <span>{{ $p->name }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    @endif
</div>
