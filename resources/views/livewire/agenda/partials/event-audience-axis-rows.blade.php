<div class="mt-3 divide-y divide-slate-100 rounded-xl border border-slate-200 dark:divide-slate-800 dark:border-slate-800">
    @foreach ($rows as $row)
        @php
            $dept = $row['department'];
            $positions = $row['positions'];
        @endphp
        <div class="px-4 py-3">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <span class="font-medium text-slate-900 dark:text-slate-100">{{ $dept->name }}</span>
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
