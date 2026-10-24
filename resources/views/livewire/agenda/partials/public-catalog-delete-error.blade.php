@if ($deleteError ?? null)
    <x-nexus.alert tone="danger" class="mb-4">{{ $deleteError }}</x-nexus.alert>
@endif
