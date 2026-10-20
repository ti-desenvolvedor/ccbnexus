<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        h2 { font-size: 13px; margin: 16px 0 6px; }
        p { margin: 0 0 8px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th, td { border-bottom: 1px solid #ccc; padding: 7px 4px; text-align: left; vertical-align: bottom; }
        th { font-size: 10px; }
        td.when { width: 22%; white-space: nowrap; }
        td.place { width: 34%; }
        td.who { width: 44%; }
        tr:nth-child(even) td { background: #f1f5f9; }
        .marking-write-line { display: inline-block; width: 92%; border-bottom: 1px solid #111; line-height: 16px; }
    </style>
</head>
<body>
    <h1>Relatório de Marcações</h1>
    <p>
        {{ $from->format('d/m/Y') }} a {{ $to->format('d/m/Y') }}
        @if ($regionalName)
            · {{ $regionalName }}
        @endif
    </p>
    <p>A lista está por tipo de evento e, dentro de cada tipo, por data e local.</p>

    @forelse ($groups as $group)
        <h2>{{ $group['name'] }}</h2>
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Local</th>
                    <th>Quem atende</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($group['rows'] as $row)
                    <tr>
                        <td class="when">{{ $row['when'] }}</td>
                        <td class="place">{{ $row['place'] }}</td>
                        <td class="who">@if (($row['attendant'] ?? '') !== ''){{ $row['attendant'] }}@else<span class="marking-write-line">&nbsp;</span>@endif</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @empty
        <p>Nenhuma marcação neste período.</p>
    @endforelse
</body>
</html>
