<x-ui.card title="{{ __('Pedidos de acesso') }}">
    <div class="mb-4">
        <label class="text-xs font-semibold text-slate-500">{{ __('Justificativa (obrigatória)') }}</label>
        <textarea wire:model="review_note" rows="2" class="mt-1 w-full min-h-[44px] rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950"></textarea>
        @error('review_note') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div class="hidden overflow-x-auto md:block">
        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase dark:bg-slate-900">
                <tr>
                    <th class="px-3 py-2">{{ __('Nome') }}</th>
                    <th class="px-3 py-2">{{ __('Email') }}</th>
                    <th class="px-3 py-2">{{ __('Regional') }}</th>
                    <th class="px-3 py-2">{{ __('Estado') }}</th>
                    <th class="px-3 py-2 text-right">{{ __('Ações') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($requests as $r)
                    <tr wire:key="ar-{{ $r->id }}">
                        <td class="px-3 py-2">{{ $r->name }}</td>
                        <td class="px-3 py-2">{{ $r->email }}</td>
                        <td class="px-3 py-2">{{ $r->regional?->name ?? '—' }}</td>
                        <td class="px-3 py-2"><x-ui.badge>{{ $r->status }}</x-ui.badge></td>
                        <td class="px-3 py-2 text-right space-x-2">
                            @if ($r->status === 'pending')
                                <button type="button" wire:click="approve({{ $r->id }})" class="min-h-[44px] text-emerald-600 hover:underline">{{ __('Aprovar') }}</button>
                                <button type="button" wire:click="reject({{ $r->id }})" class="min-h-[44px] text-rose-600 hover:underline">{{ __('Rejeitar') }}</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-nexus.empty-state :title="__('Sem pedidos')" :description="__('Pedidos públicos de acesso aparecem aqui.')" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="space-y-3 md:hidden">
        @forelse ($requests as $r)
            <div wire:key="arm-{{ $r->id }}" class="rounded-xl border border-slate-200 p-3 dark:border-slate-800">
                <div class="font-semibold">{{ $r->name }}</div>
                <div class="text-xs text-slate-500">{{ $r->email }} · {{ $r->regional?->name ?? '—' }}</div>
                <div class="mt-2"><x-ui.badge>{{ $r->status }}</x-ui.badge></div>
                @if ($r->status === 'pending')
                    <div class="mt-3 flex gap-2">
                        <button type="button" wire:click="approve({{ $r->id }})" class="min-h-[44px] flex-1 rounded-xl bg-emerald-600 text-sm font-semibold text-white">{{ __('Aprovar') }}</button>
                        <button type="button" wire:click="reject({{ $r->id }})" class="min-h-[44px] flex-1 rounded-xl bg-rose-600 text-sm font-semibold text-white">{{ __('Rejeitar') }}</button>
                    </div>
                @endif
            </div>
        @empty
            <x-nexus.empty-state :title="__('Sem pedidos')" />
        @endforelse
    </div>

    <div class="mt-4">{{ $requests->links() }}</div>
</x-ui.card>
