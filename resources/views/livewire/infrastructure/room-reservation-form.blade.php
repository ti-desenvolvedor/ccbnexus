<x-ui.card title="{{ __('Nova reserva') }}">
    @if ($meetingRooms->isEmpty())
        <x-nexus.empty-state
            :title="__('Sem salas disponíveis')"
            :description="__('Crie uma sala em Operação → Salas no seu âmbito.')"
            :href="route('infrastructure.meeting-rooms.create')"
            :action-label="__('Criar sala')"
        />
    @else
    <form wire:submit="save" data-progress="marked" class="grid w-full min-w-0 gap-4 md:grid-cols-2">
        <x-nexus.form-progress />
        <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Sala</label>
            <div class="mt-1">
                <x-nexus.searchable-select
                    data-progress-field
                    wire:model="meeting_room_id"
                    :options="$meetingRooms"
                    :placeholder="__('Selecione a sala')"
                    :search-placeholder="__('Buscar sala...')"
                />
            </div>
            @error('meeting_room_id') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Título</label>
            <input type="text" wire:model="title" data-progress-field class="mt-1 w-full min-h-[44px] rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
            @error('title') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
        </div>
        <div class="md:col-span-2">
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Notas</label>
            <textarea wire:model="notes" rows="3" data-progress="skip" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950"></textarea>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 md:col-span-2">
            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Início</label>
                <input type="datetime-local" wire:model="starts_at" data-progress-field class="mt-1 w-full min-h-[44px] rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
                @error('starts_at') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Fim</label>
                <input type="datetime-local" wire:model="ends_at" data-progress-field class="mt-1 w-full min-h-[44px] rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
                @error('ends_at') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
            </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 md:col-span-2">
            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('Setup (min)') }}</label>
                <input type="number" min="0" max="240" wire:model="setup_minutes" class="mt-1 w-full min-h-[44px] rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('Teardown (min)') }}</label>
                <input type="number" min="0" max="240" wire:model="teardown_minutes" class="mt-1 w-full min-h-[44px] rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
            </div>
        </div>
        @can('aprovar_reserva_sala')
            <label class="inline-flex min-h-[44px] items-center gap-2 text-sm md:col-span-2">
                <input type="checkbox" wire:model="requires_approval" class="rounded" />
                {{ __('Enviar para fila de aprovação') }}
            </label>
        @else
            <p class="text-xs text-slate-500 md:col-span-2">{{ __('A reserva ficará pendente até aprovação.') }}</p>
        @endcan
        <div class="flex flex-col gap-2 sm:flex-row md:col-span-2">
            <x-ui.button type="submit" class="min-h-[44px]">{{ __('Guardar') }}</x-ui.button>
            <a href="{{ route('infrastructure.room-reservations.index') }}" wire:navigate class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold dark:border-slate-800">{{ __('Cancelar') }}</a>
            <a href="{{ route('infrastructure.room-reservations.calendar') }}" wire:navigate class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold dark:border-slate-800">{{ __('Calendário') }}</a>
        </div>
    </form>
    @endif
</x-ui.card>
