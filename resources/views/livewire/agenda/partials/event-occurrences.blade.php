@if ($result->entries()->isEmpty())
    <x-nexus.empty-state
        title="Nenhum evento neste período"
        description="Ajuste a pesquisa, o tipo ou o canal, ou mude o mês."
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
@endif
