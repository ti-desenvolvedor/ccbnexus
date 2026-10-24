<x-ui.card title="{{ __('Reservas') }}">
    <div class="mb-3 flex flex-wrap gap-2">
        <a href="{{ route('infrastructure.room-reservations.create') }}" wire:navigate class="inline-flex min-h-[44px] items-center rounded-xl bg-primary-600 px-4 text-sm font-semibold text-white">{{ __('Nova reserva') }}</a>
        <a href="{{ route('infrastructure.room-reservations.calendar') }}" wire:navigate class="inline-flex min-h-[44px] items-center rounded-xl border border-slate-200 px-4 text-sm font-semibold dark:border-slate-800">{{ __('Calendário') }}</a>
    </div>

    <div class="mb-4">
        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('Justificativa (aprovar/rejeitar)') }}</label>
        <textarea wire:model="moderator_note" rows="2" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950"></textarea>
        @error('moderator_note') <x-nexus.inline-message>{{ $message }}</x-nexus.inline-message> @enderror
    </div>

    <div class="hidden overflow-x-auto md:block">
        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-600 dark:bg-slate-900 dark:text-slate-400">
                <tr>
                    <th class="px-4 py-3">{{ __('Título') }}</th>
                    <th class="px-4 py-3">{{ __('Sala') }}</th>
                    <th class="px-4 py-3">{{ __('Início') }}</th>
                    <th class="px-4 py-3">{{ __('Fim') }}</th>
                    <th class="px-4 py-3">{{ __('Estado') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Ações') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($reservations as $row)
                    <tr wire:key="rr-{{ $row->id }}">
                        <td class="px-4 py-3 font-medium">{{ $row->title }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $row->meetingRoom?->name }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $row->starts_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $row->ends_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">
                            <x-ui.badge>{{ $row->status }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-3 text-right space-x-2">
                            @can('approve', $row)
                                @if ($row->status === 'pending')
                                    <button type="button" wire:click="approve({{ $row->id }})" class="min-h-[44px] text-sm text-emerald-600 hover:underline">{{ __('Aprovar') }}</button>
                                    <button type="button" wire:click="reject({{ $row->id }})" class="min-h-[44px] text-sm text-rose-600 hover:underline">{{ __('Rejeitar') }}</button>
                                @endif
                            @endcan
                            @can('delete', $row)
                                @if (! in_array($row->status, ['cancelled'], true))
                                    <button type="button" wire:click="cancel({{ $row->id }})" class="min-h-[44px] text-sm text-slate-600 hover:underline">{{ __('Cancelar') }}</button>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-nexus.empty-state :title="__('Nenhuma reserva')" :href="route('infrastructure.room-reservations.create')" :action-label="__('Nova reserva')" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="space-y-3 md:hidden">
        @forelse ($reservations as $row)
            <div wire:key="rrm-{{ $row->id }}" class="rounded-xl border border-slate-200 p-3 dark:border-slate-800">
                <div class="flex items-start justify-between gap-2">
                    <div class="font-semibold">{{ $row->title }}</div>
                    <x-ui.badge>{{ $row->status }}</x-ui.badge>
                </div>
                <div class="mt-1 text-xs text-slate-500">{{ $row->meetingRoom?->name }} · {{ $row->starts_at->format('d/m H:i') }}–{{ $row->ends_at->format('H:i') }}</div>
                <div class="mt-3 flex flex-wrap gap-2">
                    @can('approve', $row)
                        @if ($row->status === 'pending')
                            <button type="button" wire:click="approve({{ $row->id }})" class="min-h-[44px] flex-1 rounded-xl bg-emerald-600 text-sm font-semibold text-white">{{ __('Aprovar') }}</button>
                            <button type="button" wire:click="reject({{ $row->id }})" class="min-h-[44px] flex-1 rounded-xl bg-rose-600 text-sm font-semibold text-white">{{ __('Rejeitar') }}</button>
                        @endif
                    @endcan
                </div>
            </div>
        @empty
            <x-nexus.empty-state :title="__('Nenhuma reserva')" :href="route('infrastructure.room-reservations.create')" :action-label="__('Nova reserva')" />
        @endforelse
    </div>

    <div class="mt-4">{{ $reservations->links() }}</div>
</x-ui.card>
