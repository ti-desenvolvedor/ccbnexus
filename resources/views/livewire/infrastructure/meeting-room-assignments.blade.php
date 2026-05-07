<x-ui.card title="Atribuições (salas partilhadas)">
    <p class="mb-4 text-sm text-slate-600 dark:text-slate-300">Quem mais pode utilizar esta sala na mesma regional.</p>
    <div class="mb-6 flex flex-wrap items-end gap-3 border-b border-slate-100 pb-6 dark:border-slate-800">
        <div>
            <label class="block text-xs font-semibold text-slate-500">Tipo</label>
            <select wire:model.live="assignable_type" class="mt-1 rounded-lg border border-slate-200 px-2 py-1.5 text-sm dark:border-slate-700 dark:bg-slate-900">
                <option value="{{ \App\Models\Regional::class }}">Regional</option>
                <option value="{{ \App\Models\Administration::class }}">Administração</option>
                <option value="{{ \App\Models\PrayerHouse::class }}">Casa de oração</option>
            </select>
        </div>
        <div class="min-w-[12rem]">
            <label class="block text-xs font-semibold text-slate-500">Registo</label>
            <select wire:model="assignable_id" class="mt-1 w-full rounded-lg border border-slate-200 px-2 py-1.5 text-sm dark:border-slate-700 dark:bg-slate-900">
                @if ($assignable_type === \App\Models\Regional::class)
                    @foreach ($regionals as $r)
                        <option value="{{ $r->id }}">{{ $r->name }}</option>
                    @endforeach
                @elseif ($assignable_type === \App\Models\Administration::class)
                    @foreach ($administrations as $a)
                        <option value="{{ $a->id }}">{{ $a->name }}</option>
                    @endforeach
                @else
                    @foreach ($houses as $h)
                        <option value="{{ $h->id }}">{{ $h->name }}</option>
                    @endforeach
                @endif
            </select>
        </div>
        <x-ui.button type="button" wire:click="addAssignment" size="sm">Adicionar</x-ui.button>
    </div>
    <ul class="divide-y divide-slate-100 dark:divide-slate-800">
        @forelse ($meetingRoom->assignments as $as)
            <li class="flex items-center justify-between py-2 text-sm" wire:key="asg-{{ $as->id }}">
                <span>{{ class_basename($as->assignable_type) }}: {{ $as->assignable?->name ?? ('#'.$as->assignable_id) }}</span>
                <button type="button" wire:click="remove({{ $as->id }})" class="text-rose-600 hover:underline">Remover</button>
            </li>
        @empty
            <li class="py-4 text-slate-500">Sem atribuições extra.</li>
        @endforelse
    </ul>
    @error('assignable_type') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
    @error('assignable_id') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
</x-ui.card>
