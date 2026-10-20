<x-ui.card title="Atribuições (salas partilhadas)">
    <p class="mb-4 text-sm text-slate-600 dark:text-slate-300">Quem mais pode utilizar esta sala na mesma regional.</p>
    <div class="mb-6 flex flex-wrap items-end gap-3 border-b border-slate-100 pb-6 dark:border-slate-800">
        <div>
            <label class="block text-xs font-semibold text-slate-500">Tipo</label>
            <x-nexus.searchable-select
                wire:model.live="assignable_type"
                class="mt-1 min-w-[12rem]"
                :search-placeholder="__('Buscar tipo...')"
                :options="[
                    ['value' => \App\Models\Regional::class, 'label' => __('Regional')],
                    ['value' => \App\Models\Administration::class, 'label' => __('Administração')],
                    ['value' => \App\Models\PrayerHouse::class, 'label' => __('Casa de oração')],
                ]"
            />
        </div>
        <div class="min-w-[12rem]">
            <label class="block text-xs font-semibold text-slate-500">Registo</label>
            <x-nexus.searchable-select
                wire:model="assignable_id"
                class="mt-1"
                :search-placeholder="__('Buscar registo...')"
                :options="match ($assignable_type) {
                    \App\Models\Regional::class => $regionals,
                    \App\Models\Administration::class => $administrations,
                    default => $houses,
                }"
            />
        </div>
        <x-ui.button type="button" wire:click="addAssignment" size="sm">{{ __('Adicionar') }}</x-ui.button>
    </div>
    <ul class="divide-y divide-slate-100 dark:divide-slate-800">
        @forelse ($meetingRoom->assignments as $as)
            <li class="flex items-center justify-between py-2 text-sm" wire:key="asg-{{ $as->id }}">
                <span>{{ class_basename($as->assignable_type) }}: {{ $as->assignable?->name ?? ('#'.$as->assignable_id) }}</span>
                <button type="button" wire:click="remove({{ $as->id }})" class="text-rose-600 hover:underline">{{ __('Remover') }}</button>
            </li>
        @empty
            <li class="py-4 text-slate-500">Sem atribuições extra.</li>
        @endforelse
    </ul>
    @error('assignable_type') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
    @error('assignable_id') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
</x-ui.card>
