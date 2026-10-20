<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 14mm 14mm 16mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: DejaVu Sans, sans-serif; color: #1c1917; font-size: 9.5pt; }
        h1, h2, p { margin: 0; }
        .kicker { font-size: 9pt; letter-spacing: 0.04em; color: #44403c; text-transform: uppercase; }
        h1 { margin-top: 2mm; font-family: DejaVu Serif, serif; font-size: 22pt; font-weight: normal; color: #1c3328; }
        .lead { margin-top: 2mm; font-size: 10pt; color: #44403c; }
        .rule { width: 28mm; height: 0; border-top: 0.8pt solid #d6c7a1; margin: 4mm 0 6mm; }

        .series { margin: 0 0 6mm; page-break-inside: avoid; }
        .series h2 { background: #1c3328; color: #f4f1ea; font-family: DejaVu Serif, serif; font-size: 12pt; font-weight: normal; padding: 2mm 3mm; }
        table.events { width: 100%; border-collapse: collapse; }
        table.events th { text-align: left; font-size: 8pt; color: #1c3328; padding: 1.6mm 2mm; border-bottom: 0.6pt solid #1c3328; }
        table.events td { padding: 1.8mm 2mm; vertical-align: top; border-bottom: 0.3pt solid #e7e5e4; }
        table.events .title { font-weight: bold; }
        table.events .meta { margin-top: 0.4mm; font-size: 8pt; color: #44403c; }
        table.events .months { width: 62mm; color: #1c3328; }
        .foot { margin-top: 4mm; font-size: 8pt; color: #44403c; }
    </style>
</head>
<body>
    <p class="kicker">{{ $regionalName }}</p>
    <h1>Agenda {{ $year }}</h1>
    <p class="lead">Resumo por séries. Cada evento aparece uma vez, com os meses em que acontece.</p>
    <div class="rule"></div>

    @foreach ($series as $block)
        <div class="series">
            <h2>{{ $block['title'] }}</h2>
            <table class="events">
                <thead>
                    <tr>
                        <th>Evento</th>
                        <th>Meses</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($block['events'] as $event)
                        <tr>
                            <td>
                                <div class="title">{{ $event['title'] }}</div>
                                @if ($event['time'] !== '' || $event['place'] !== '')
                                    <div class="meta">{{ trim($event['time'].($event['time'] !== '' && $event['place'] !== '' ? ' · ' : '').$event['place']) }}</div>
                                @endif
                            </td>
                            <td class="months">{{ $event['months'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach

    <p class="foot">{{ $seriesCount }} séries · {{ $eventCount }} eventos · fonte: agenda cadastrada 2026</p>
</body>
</html>
