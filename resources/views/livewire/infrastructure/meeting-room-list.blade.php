<div class="space-y-4">
    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Pesquisar…" class="max-w-md w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950" />
    <x-ui.card>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-600 dark:bg-slate-900 dark:text-slate-400">
                    <tr>
                        <th class="px-4 py-3">Nome</th>
                        <th class="px-4 py-3">Dono</th>
                        <th class="px-4 py-3">Cap.</th>
                        <th class="px-4 py-3 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($meetingRooms as $row)
                        <tr wire:key="mr-{{ $row->id }}">
                            <td class="px-4 py-3 font-medium">{{ $row->name }}</td>
                            <td class="px-4 py-3 text-slate-600">
                                @if ($row->owner instanceof \App\Models\Administration)
                                    Adm: {{ $row->owner->name }}
                                @elseif ($row->owner instanceof \App\Models\PrayerHouse)
                                    Casa: {{ $row->owner->name }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $row->capacity ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                @can('update', $row)
                                    <a href="{{ route('infrastructure.meeting-rooms.edit', $row) }}" wire:navigate class="text-primary-600 hover:underline">Editar</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">Nenhum registo.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $meetingRooms->links() }}</div>
    </x-ui.card>
</div>
