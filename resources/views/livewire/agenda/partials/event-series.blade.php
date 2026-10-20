<p class="mb-3 text-sm text-slate-700 dark:text-slate-200">Séries da regional, não o mês.</p>

@if ($result->entries()->isEmpty())
    <x-nexus.empty-state
        title="Nenhuma série neste contexto"
        description="Escolha a regional no seletor do topo, ou crie a primeira série."
        :href="$canCreate ? route('agenda.events.create') : null"
        action-label="Novo evento"
    />
@else
    <div class="space-y-3 md:hidden">
        @foreach ($events as $e)
            @include('livewire.agenda.partials.event-card', ['e' => $e])
        @endforeach
    </div>
    <div class="hidden md:block">
        @include('livewire.agenda.partials.event-table')
    </div>
    @if ($events instanceof \Illuminate\Contracts\Pagination\Paginator)
        <div class="mt-4">{{ $events->links() }}</div>
    @endif
@endif
