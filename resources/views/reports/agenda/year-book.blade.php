<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 14mm 16mm 16mm; }
        html, body { background: #ffffff; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: DejaVu Sans, sans-serif; color: #1c1917; font-size: 10pt; }
        .break { page-break-before: always; }
        h1, h2, p { margin: 0; }

        .cover { width: 100%; border-collapse: collapse; }
        .cover td { background: #1c3328; color: #f4f1ea; }
        .cover-top { height: 155mm; vertical-align: top; padding: 18mm 14mm 0; }
        .cover-bottom { height: 70mm; vertical-align: bottom; padding: 0 14mm 14mm; }
        .cover h1 { font-family: DejaVu Serif, serif; font-size: 28pt; font-weight: normal; line-height: 1; }
        .cover h1 span { display: block; margin-top: 4mm; font-size: 54pt; letter-spacing: -0.03em; }
        .rule { width: 28mm; height: 0; border-top: 0.8pt solid #d6c7a1; margin: 8mm 0 0; }
        .cover .regional { font-family: DejaVu Serif, serif; font-size: 16pt; line-height: 1.25; }
        .cover .span { margin-top: 3mm; font-size: 11pt; color: #e7e1d4; }
        .cover .note { margin-top: 4mm; font-size: 9pt; color: #e7e1d4; }

        .section-kicker { font-family: DejaVu Serif, serif; font-size: 22pt; font-weight: normal; color: #1c3328; }
        .section-meta { margin-top: 1.5mm; font-size: 9pt; color: #44403c; }
        .head { width: 100%; border-collapse: collapse; margin: 0 0 8mm; }
        .head td { vertical-align: bottom; }
        .head .year { text-align: right; font-family: DejaVu Serif, serif; font-size: 14pt; color: #1c3328; }

        table.index { width: 100%; border-collapse: collapse; margin-top: 6mm; }
        table.index td { padding: 2.4mm 0; vertical-align: baseline; }
        table.index .name { border-bottom: 0.4pt dotted #a8a29e; font-size: 12pt; }
        table.index .detail { width: 42mm; padding-left: 4mm; text-align: right; font-size: 9pt; color: #44403c; }
        table.index .page { width: 14mm; padding-left: 4mm; text-align: right; font-family: DejaVu Serif, serif; font-size: 12pt; color: #1c3328; }
        table.index tr.lead td { padding-top: 0; }
        table.index tr.lead .name { font-family: DejaVu Serif, serif; font-size: 13pt; }

        table.listing { width: 100%; border-collapse: collapse; }
        table.listing th { text-align: left; font-size: 8pt; font-weight: bold; color: #1c3328; letter-spacing: 0; padding: 0 2mm 1.5mm; border-bottom: 0.8pt solid #1c3328; }
        table.listing td { padding: 1.8mm 2mm; vertical-align: top; border-bottom: 0.3pt solid #e7e5e4; }
        table.listing .title { font-weight: bold; }
        table.listing .meta { margin-top: 0.4mm; font-size: 8pt; color: #44403c; }
        table.listing .months { width: 62mm; color: #1c3328; }
        table.listing .band td { background: #1c3328; color: #f4f1ea; font-family: DejaVu Serif, serif; font-size: 11pt; border-bottom: 0; padding: 2mm 2mm; }
        .listing-lead { margin: -4mm 0 5mm; font-size: 9pt; color: #44403c; }

        table.notes { width: 100%; border-collapse: collapse; }
        table.notes td { height: 8mm; border-bottom: 0.4pt solid #d6d3d1; font-size: 1pt; line-height: 1; }

        table.cal { width: 100%; border-collapse: collapse; margin: 0 0 7mm; }
        table.cal th { font-size: 8pt; font-weight: bold; color: #1c3328; text-align: center; padding: 0 0 1.5mm; border-bottom: 0.6pt solid #1c3328; }
        table.cal td { width: 14.28%; height: 8mm; text-align: center; vertical-align: middle; font-size: 9pt; color: #78716c; border-bottom: 0.3pt solid #e7e5e4; }
        table.cal td.busy { color: #1c3328; font-weight: bold; }
        table.cal td.holiday { color: #92400e; font-weight: bold; }

        .day { margin: 0 0 3.5mm; }
        .day-name { font-family: DejaVu Serif, serif; font-size: 12pt; color: #1c3328; border-bottom: 0.4pt solid #d6d3d1; padding-bottom: 1mm; margin-bottom: 1.5mm; }
        table.entry { width: 100%; border-collapse: collapse; margin: 0 0 1.5mm; }
        table.entry td { vertical-align: top; }
        table.entry .when { width: 32mm; color: #44403c; }
        table.entry .what { font-weight: bold; }
        table.entry .where { margin-top: 0.3mm; font-size: 8.5pt; color: #44403c; }
        .empty { margin-top: 4mm; font-size: 11pt; color: #44403c; }

        table.foot { width: 100%; border-collapse: collapse; margin-top: 6mm; }
        table.foot td { border-top: 0.4pt solid #d6d3d1; padding-top: 2mm; font-size: 8pt; color: #44403c; }
        table.foot .num { text-align: right; font-family: DejaVu Serif, serif; font-size: 10pt; color: #1c3328; }
    </style>
</head>
<body>
@foreach ($pages as $page)
    <div class="sheet {{ $loop->first ? '' : 'break' }}">
        @if ($page['kind'] === 'cover')
            <table class="cover">
                <tr>
                    <td class="cover-top">
                        <h1>Agenda <span>{{ $year }}</span></h1>
                        <div class="rule"></div>
                    </td>
                </tr>
                <tr>
                    <td class="cover-bottom">
                        <p class="regional">{{ $regionalName !== '' ? $regionalName : 'Agenda regional' }}</p>
                        <p class="span">Janeiro a dezembro</p>
                        @if ($filterNote !== '')
                            <p class="note">{{ $filterNote }}</p>
                        @endif
                        @if ($includesDrafts)
                            <p class="note">Inclui rascunhos.</p>
                        @endif
                        @if ($truncated)
                            <p class="note">Um mês passou de 500 ocorrências. Esse mês está incompleto.</p>
                        @endif
                    </td>
                </tr>
            </table>
        @elseif ($page['kind'] === 'index')
            @include('reports.agenda.partials.year-book-heading', ['title' => 'Índice', 'continued' => false])
            <table class="index">
                @foreach ($page['entries'] as $entry)
                    <tr class="{{ $loop->first ? 'lead' : '' }}">
                        <td class="name">{{ $entry['label'] }}</td>
                        <td class="detail">{{ $entry['detail'] }}</td>
                        <td class="page">{{ $entry['page'] }}</td>
                    </tr>
                @endforeach
            </table>
            @include('reports.agenda.partials.year-book-footer', ['page' => $page])
        @elseif ($page['kind'] === 'listing')
            @include('reports.agenda.partials.year-book-heading', ['title' => 'Listagem geral', 'continued' => $page['continued']])
            @if (! $page['continued'])
                <p class="listing-lead">Cada evento aparece uma vez, com os meses em que acontece.</p>
            @endif
            @if ($page['rows'] === [])
                <p class="empty">Nenhum compromisso neste ano.</p>
            @else
                <table class="listing">
                    <thead>
                        <tr>
                            <th>Evento</th>
                            <th class="months">Meses</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($page['rows'] as $row)
                            @if ($row['kind'] === 'band')
                                <tr class="band"><td colspan="2">{{ $row['label'] }}</td></tr>
                            @else
                                <tr>
                                    <td>
                                        <div class="title">{{ $row['title'] }}</div>
                                        @if (($row['meta'] ?? '') !== '')
                                            <div class="meta">{{ $row['meta'] }}</div>
                                        @endif
                                    </td>
                                    <td class="months">{{ $row['months'] }}</td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            @endif
            @include('reports.agenda.partials.year-book-footer', ['page' => $page])
        @elseif ($page['kind'] === 'notes')
            @include('reports.agenda.partials.year-book-heading', ['title' => 'Anotações', 'continued' => false])
            <table class="notes">
                @for ($line = 0; $line < 24; $line++)
                    <tr><td>&nbsp;</td></tr>
                @endfor
            </table>
            @include('reports.agenda.partials.year-book-footer', ['page' => $page])
        @elseif ($page['kind'] === 'end-cover')
            <table class="cover">
                <tr>
                    <td class="cover-top">
                        <h1>Fim <span>{{ $year }}</span></h1>
                        <div class="rule"></div>
                    </td>
                </tr>
                <tr>
                    <td class="cover-bottom">
                        <p class="regional">{{ $regionalName !== '' ? $regionalName : 'Agenda regional' }}</p>
                        <p class="span">Janeiro a dezembro</p>
                    </td>
                </tr>
            </table>
        @else
            @include('reports.agenda.partials.year-book-heading', ['title' => $page['name'], 'continued' => $page['continued']])
            @if ($page['calendar'] !== [])
                <table class="cal">
                    <thead>
                        <tr>
                            <th>Seg</th><th>Ter</th><th>Qua</th><th>Qui</th><th>Sex</th><th>Sáb</th><th>Dom</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($page['calendar'] as $week)
                            <tr>
                                @foreach ($week as $day)
                                    <td class="{{ $day['inMonth'] ? trim(($day['busy'] ? 'busy ' : '').(($day['holiday'] ?? false) ? 'holiday' : '')) : '' }}">{{ $day['inMonth'] ? $day['day'] : '' }}@if ($day['inMonth'] && ($day['holiday'] ?? false))*@endif</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if ($page['hasHoliday'] ?? false)
                    <p class="section-meta" style="margin: -5mm 0 5mm;">* Feriado, Enem ou eleição. Não entra na lista de compromissos.</p>
                @endif
            @endif
            @if ($page['empty'])
                <p class="empty">Nenhum compromisso neste mês.</p>
            @else
                @foreach ($page['rows'] as $row)
                    @if ($row['kind'] === 'day')
                        <div class="day-name">{{ $row['label'] }}</div>
                    @else
                        <table class="entry">
                            <tr>
                                <td class="when">{{ $row['time'] }}</td>
                                <td>
                                    <div class="what">{{ $row['title'] }}@if (! empty($row['status'])) · {{ $row['status'] }}@endif</div>
                                    <div class="where">{{ $row['type'] }} · {{ $row['place'] }}@if (($row['attendant'] ?? '—') !== '—') · {{ $row['attendant'] }}@endif</div>
                                </td>
                            </tr>
                        </table>
                    @endif
                @endforeach
            @endif
            @include('reports.agenda.partials.year-book-footer', ['page' => $page])
        @endif
    </div>
@endforeach
</body>
</html>
