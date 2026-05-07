<div class="mx-auto max-w-xl space-y-6">
    <div class="flex gap-2 text-xs font-semibold text-slate-500 dark:text-slate-400">
        <span @class(['text-primary-600 dark:text-primary-400' => $step === 1])>1. Participação</span>
        <span>→</span>
        <span @class(['text-primary-600 dark:text-primary-400' => $step === 2])>2. Refeições</span>
    </div>

    @if ($step === 1)
        <x-ui.card title="Confirmação de participação">
            <p class="text-sm text-slate-600 dark:text-slate-300">
                Evento: <strong>{{ $event->title }}</strong><br />
                <span class="text-slate-500">{{ $event->starts_at->format('d/m/Y H:i') }}</span>
            </p>

            <div class="mt-4 space-y-3">
                <p class="text-sm font-medium text-slate-800 dark:text-slate-100">
                    @if ($event->attendance_mode === 'online_only')
                        Confirma que vai acompanhar este evento online?
                    @else
                        Confirma a sua presença neste evento?
                    @endif
                </p>
                <label class="flex items-center gap-2 text-sm">
                    <input type="radio" wire:model.live="participation" value="yes" class="text-primary-600" />
                    Sim
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input type="radio" wire:model.live="participation" value="no" class="text-primary-600" />
                    Não
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input type="radio" wire:model.live="participation" value="maybe" class="text-primary-600" />
                    Ainda não sei
                </label>
                @error('participation') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            @if ($event->attendance_mode === 'hybrid' && $participation === 'yes')
                <div class="mt-4 space-y-2 border-t border-slate-100 pt-4 dark:border-slate-800">
                    <p class="text-sm font-medium text-slate-800 dark:text-slate-100">Como pretende participar?</p>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="radio" wire:model="presence_mode" value="in_person" class="text-primary-600" />
                        Presencialmente no local
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="radio" wire:model="presence_mode" value="online" class="text-primary-600" />
                        Online (remoto)
                    </label>
                    @error('presence_mode') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            @endif

            <div class="mt-6 flex flex-wrap gap-2">
                <x-ui.button type="button" wire:click="proceedFromStep1">Continuar</x-ui.button>
                <a href="{{ route('agenda.events.index') }}" wire:navigate class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold dark:border-slate-800">Voltar à lista</a>
            </div>
        </x-ui.card>
    @endif

    @if ($step === 2)
        <x-ui.card title="Refeições no evento">
            <p class="text-sm text-slate-600 dark:text-slate-300">
                Indique em que refeições prevê participar, para efeitos de logística e cantina.
            </p>
            <div class="mt-4 space-y-2">
                @if ($event->meal_coffee)
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="meal_coffee" class="rounded" /> Participo no café servido no evento</label>
                @endif
                @if ($event->meal_lunch)
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="meal_lunch" class="rounded" /> Participo no almoço</label>
                @endif
                @if ($event->meal_snack)
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="meal_snack" class="rounded" /> Participo no lanche</label>
                @endif
                @if ($event->meal_dinner)
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="meal_dinner" class="rounded" /> Participo no jantar</label>
                @endif
            </div>
            <div class="mt-6 flex flex-wrap gap-2">
                <x-ui.button type="button" variant="secondary" wire:click="backToStep1">Anterior</x-ui.button>
                <x-ui.button type="button" wire:click="saveMealsAndFinish">Guardar confirmação</x-ui.button>
            </div>
        </x-ui.card>
    @endif
</div>
