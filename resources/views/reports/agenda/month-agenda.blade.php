<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 12mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #111; }
        h1 { font-size: 14pt; margin: 0; }
        .meta { margin: 2px 0 8px; color: #333; }
        .note { margin: 0 0 6px; font-weight: bold; }
        h2 { font-size: 11pt; margin: 10px 0 4px; page-break-after: avoid; }
        .event { margin: 0 0 6px; page-break-inside: avoid; }
        .title { font-weight: bold; }
        .muted { color: #333; }
    </style>
</head>
<body>
    <h1>{{ $regionalName }}</h1>
    <p class="meta">{{ $monthLabel }}</p>
    @if ($result->includesDrafts)
        <p class="note">Inclui rascunhos</p>
    @endif
    @if ($result->truncated)
        <p class="note">Primeiras 500 ocorrências. O período está incompleto.</p>
    @endif

    @forelse ($days as $day)
        <h2>{{ $day['heading'] }}</h2>
        @foreach ($day['events'] as $event)
            <div class="event">
                <div class="title">{{ $event['time'] }} {{ $event['title'] }}@if ($event['status']) — {{ $event['status'] }}@endif</div>
                <div class="muted">{{ $event['type'] }} · {{ $event['place'] }} · {{ $event['attendant'] }}
                    @if ($event['channels'] !== [])
                        · {{ implode(', ', $event['channels']) }}
                    @endif
                </div>
            </div>
        @endforeach
    @empty
        <p>Nenhum evento neste período.</p>
    @endforelse
</body>
</html>
