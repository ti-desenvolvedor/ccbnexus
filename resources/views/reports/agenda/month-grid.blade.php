<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 10mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8pt; color: #111; }
        h1 { font-size: 14pt; margin: 0; }
        .meta { margin: 2px 0 8px; color: #333; }
        .note { margin: 0 0 6px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 0.4pt solid #ccc; vertical-align: top; padding: 3px; }
        th { background: #f3f4f6; text-align: center; }
        td.out { background: #fafafa; color: #9ca3af; }
        .chip { margin: 0 0 2px; }
        .empty { margin-top: 12px; }
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

    @if ($result->entries()->isEmpty())
        <p class="empty">Nenhum evento neste período.</p>
    @endif

    <table>
        <thead>
            <tr>
                <th>Seg</th><th>Ter</th><th>Qua</th><th>Qui</th><th>Sex</th><th>Sáb</th><th>Dom</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($weeks as $week)
                <tr>
                    @foreach ($week as $day)
                        <td class="{{ $day['inMonth'] ? '' : 'out' }}">
                            <strong>{{ $day['day'] }}</strong>
                            @foreach ($day['events'] as $event)
                                <div class="chip">{{ $event['time'] }} {{ $event['title'] }}@if ($event['status']) ({{ $event['status'] }})@endif</div>
                            @endforeach
                            @if ($day['more'] > 0)
                                <div class="chip">+{{ $day['more'] }}</div>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
