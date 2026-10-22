@php
    // Arial Narrow fica em cache dentro do chroot do DomPDF. Sem ele, o boletim usa DejaVu.
    $fontCache = storage_path('fonts');
    if (! is_dir($fontCache)) {
        @mkdir($fontCache, 0755, true);
    }
    $narrowRegular = $fontCache.DIRECTORY_SEPARATOR.'arialn.ttf';
    $narrowBold = $fontCache.DIRECTORY_SEPARATOR.'arialnb.ttf';
    if (! is_file($narrowRegular) && is_file('C:/Windows/Fonts/arialn.ttf')) {
        @copy('C:/Windows/Fonts/arialn.ttf', $narrowRegular);
    }
    if (! is_file($narrowBold) && is_file('C:/Windows/Fonts/arialnb.ttf')) {
        @copy('C:/Windows/Fonts/arialnb.ttf', $narrowBold);
    }
    $useNarrow = is_file($narrowRegular) && is_file($narrowBold);
    $narrowRegularUrl = str_replace('\\', '/', $narrowRegular);
    $narrowBoldUrl = str_replace('\\', '/', $narrowBold);

    // Mesmas medidas do Word (BaptismListDocumentLayout).
    $layout = \App\Services\BaptismListDocumentLayout::class;
    $bodyPt = $layout::BODY_PT;
    $headingPt = $layout::HEADING_PT;
    $titlePt = $layout::TITLE_PT;
    $subPt = $layout::SUB_PT;
    $metaPt = $layout::META_PT;
    $lineHeight = $layout::LINE_HEIGHT;
    $marginTopMm = $layout::MARGIN_TOP_CM * 10;
    $marginSideMm = $layout::MARGIN_SIDE_CM * 10;
    $marginBottomMm = $layout::MARGIN_BOTTOM_CM * 10;
    $columnGapMm = $layout::COLUMN_GAP_CM * 10;
    $colDate = 16;
    $colDay = 13;
    $colTime = 17;
    $colLoc = 32;
    $colAtt = 22;
    $colLocWide = 100 - $colDate - $colDay - $colTime;
    $mmToPt = 72 / 25.4;
    $pageWidthPt = 595.28;
    $pageHeightPt = 841.89 - (($marginTopMm + $marginBottomMm) * $mmToPt);
    $contentWidthPt = $pageWidthPt - (2 * $marginSideMm * $mmToPt);
    $columnInnerPt = ($contentWidthPt / 2) - (($columnGapMm * $mmToPt) / 2);
    $runningPt = ($metaPt * $lineHeight) + 4;
    // Igual ao Word: o cabeçalho entra na coluna da esquerda, as duas colunas começam no topo.
    $columnHeightFirst = max(160, $pageHeightPt - 4);
    $columnHeightNext = max(160, $pageHeightPt - $runningPt);
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <style>
        @if ($useNarrow)
        @font-face {
            font-family: "Arial Narrow";
            font-style: normal;
            font-weight: normal;
            src: url("{{ $narrowRegularUrl }}") format("truetype");
        }
        @font-face {
            font-family: "Arial Narrow";
            font-style: normal;
            font-weight: bold;
            src: url("{{ $narrowBoldUrl }}") format("truetype");
        }
        @endif

        @page {
            {{ $layout::marginCss() }}
        }

        body {
            font-family: {!! $useNarrow ? '"Arial Narrow", ' : '' !!}DejaVu Sans, sans-serif;
            font-size: {{ $bodyPt }}pt;
            color: #000;
            line-height: {{ $lineHeight }};
        }

        .doc-title, .doc-sub, .provisional {
            text-align: center;
            margin: 0;
            padding: 0;
        }
        .doc-title {
            font-size: {{ $titlePt }}pt;
            font-weight: bold;
        }
        .doc-sub {
            font-size: {{ $subPt }}pt;
            font-weight: bold;
        }

        .provisional {
            text-align: center;
            color: #006600;
            font-size: {{ $bodyPt }}pt;
            font-weight: bold;
            margin: 0 0 2px;
            letter-spacing: 0.3px;
        }

        .running {
            font-size: {{ $metaPt }}pt;
            font-weight: bold;
            text-align: center;
            margin: 0 0 4px;
        }

        table.sheet {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin: 0;
        }
        table.sheet td.col {
            width: 50%;
            vertical-align: top;
            word-wrap: break-word;
        }
        table.sheet td.col-left { padding-right: {{ $layout::columnPaddingMm() }}mm; }
        table.sheet td.col-right { padding-left: {{ $layout::columnPaddingMm() }}mm; }

        h2.section {
            font-size: {{ $headingPt }}pt;
            font-weight: bold;
            text-align: center;
            background: #B3DDF2;
            border: 0.5pt solid #000;
            margin: 0;
            padding: 0.2pt 2px;
            line-height: 1;
            page-break-after: avoid;
            page-break-inside: avoid;
        }

        h3.group {
            font-size: {{ $headingPt }}pt;
            font-weight: bold;
            text-align: center;
            background: #B3DDF2;
            border: 0.5pt solid #000;
            margin: 0;
            padding: 0.2pt 2px;
            line-height: 1;
            page-break-after: avoid;
            page-break-inside: avoid;
        }

        table.list {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            font-size: {{ $bodyPt }}pt;
            table-layout: fixed;
        }
        table.list tr { page-break-inside: avoid; }
        table.list td {
            border: 0.4pt solid #000;
            vertical-align: middle;
            padding: 0 0.6pt;
            line-height: 1;
            word-wrap: break-word;
        }
        table.list .date { width: {{ $colDate }}%; text-align: center; white-space: nowrap; }
        table.list .day { width: {{ $colDay }}%; text-align: center; white-space: nowrap; }
        table.list .time { width: {{ $colTime }}%; text-align: center; white-space: nowrap; }
        table.list .loc { width: {{ $colLoc }}%; }
        table.list .att { width: {{ $colAtt }}%; text-align: center; }
        table.list .loc-wide { width: {{ $colLocWide }}%; }
        table.list tr.alt td { background: #D9D9D9; }

        .note, .intro {
            margin: 0 0 1px;
            font-size: {{ $bodyPt }}pt;
            line-height: 1;
            text-align: justify;
            background: transparent;
        }
        .note-title {
            font-size: {{ $bodyPt }}pt;
            font-weight: bold;
            text-align: center;
            margin: 4px 0 1px;
        }
        ul.slots {
            margin: 1px 0 3px 12px;
            padding: 0;
            font-size: {{ $bodyPt }}pt;
        }
        ul.slots li { margin-bottom: 1px; }
        .note b, .note strong { font-weight: bold; }
        .note i, .note em { font-style: italic; }
        .note u { text-decoration: underline; }
        .note ul { margin: 0 0 0 12px; padding: 0; }
        .note li { margin: 0; }

        p, ul, ol, li { margin: 0; padding: 0; line-height: 1; }
        .footer {
            margin: 4px 0 0;
            font-size: {{ $bodyPt }}pt;
            text-align: justify;
        }
        .verso {
            position: fixed;
            bottom: -{{ number_format(max(4, $marginBottomMm - 2), 1, '.', '') }}mm;
            left: 0;
            width: 100%;
            height: {{ number_format(max(4, $marginBottomMm - 3), 1, '.', '') }}mm;
            overflow: hidden;
            font-size: {{ $metaPt }}pt;
            line-height: 1.1;
            border-top: 0.4pt solid #000;
            padding-top: 1pt;
        }
        .verso table { width: 100%; border-collapse: collapse; }
        .verso td { vertical-align: middle; padding: 0; border: 0; }
        .verso .mid { text-align: center; }
        .verso .right { text-align: right; }
        .pagenum:before { content: counter(page); }
    </style>
</head>
<body>
@php
    $isDraft = ($report->status ?? null) === \App\Models\BaptismListReport::STATUS_DRAFT;
    $sectionMap = $sections instanceof \Illuminate\Support\Collection ? $sections->all() : $sections;
    $blocks = \App\Services\BaptismListDocumentBlocks::build($report, $sectionMap);

    // Entrelinha simples do Word (fonte estreita + margem da célula).
    $linePt = $useNarrow ? $layout::NARROW_LINE_PT : ($bodyPt * 1.2);
    $rowChrome = 1.15;
    $charPt = $bodyPt * ($useNarrow ? 0.40 : 0.52);
    $charsPerLine = max(22, (int) floor($columnInnerPt / $charPt));
    $firstCap = $columnHeightFirst - 10;
    $nextCap = $columnHeightNext - 6;
    $atomCap = min($firstCap, $nextCap);

    $rowLines = static function (string $html) use ($columnInnerPt, $charPt, $colDate, $colDay, $colTime, $colLoc, $colAtt, $colLocWide): int {
        if (! preg_match_all('/<td\b([^>]*)>(.*?)<\/td>/is', $html, $cells, PREG_SET_ORDER)) {
            return 1;
        }
        $widths = [
            'loc-wide' => $colLocWide / 100,
            'date' => $colDate / 100,
            'day' => $colDay / 100,
            'time' => $colTime / 100,
            'loc' => $colLoc / 100,
            'att' => $colAtt / 100,
        ];
        $max = 1;
        foreach ($cells as $cell) {
            $class = '';
            if (preg_match('/class="([^"]+)"/', $cell[1], $classMatch)) {
                $class = $classMatch[1];
            }
            $pct = 1.0;
            foreach ($widths as $name => $value) {
                if (preg_match('/(?:^|\s)'.preg_quote($name, '/').'(?:\s|$)/', $class)) {
                    $pct = $value;
                    break;
                }
            }
            $nowrap = (bool) preg_match('/(?:^|\s)(?:date|day|time)(?:\s|$)/', $class);
            $innerPt = max(16.0, ($columnInnerPt * $pct) - 5);
            $chars = max(4, (int) floor($innerPt / $charPt));
            $broken = preg_replace('/<br\s*\/?>/i', "\n", $cell[2]) ?? $cell[2];
            $plain = html_entity_decode(strip_tags($broken), ENT_QUOTES, 'UTF-8');
            $plain = str_replace(["\xc2\xa0", '‑'], [' ', '-'], $plain);
            $parts = preg_split("/\n+/u", $plain) ?: [];
            $lines = 0;
            foreach ($parts as $part) {
                $part = trim(preg_replace('/\s+/u', ' ', $part) ?? '');
                if ($part === '') {
                    continue;
                }
                $lines += $nowrap ? 1 : max(1, (int) ceil(mb_strlen($part) / $chars));
            }
            $max = max($max, max(1, $lines));
        }

        return $max;
    };

    $textLines = static function (string $html) use ($charsPerLine): int {
        $broken = preg_replace('/<br\s*\/?>|<\/p>|<\/div>|<\/li>|<\/h[1-6]>/i', "\n", $html) ?? $html;
        $plain = html_entity_decode(strip_tags($broken), ENT_QUOTES, 'UTF-8');
        $plain = str_replace("\xc2\xa0", ' ', $plain);
        $parts = preg_split("/\n+/u", $plain) ?: [];
        $lines = 0;
        foreach ($parts as $part) {
            $part = trim(preg_replace('/\s+/u', ' ', $part) ?? '');
            if ($part === '') {
                continue;
            }
            $lines += max(1, (int) ceil(mb_strlen($part) / $charsPerLine));
        }

        return max(1, $lines);
    };

    $htmlHeight = static function (string $html) use ($textLines, $linePt): float {
        $heads = preg_match_all('/<h[23]\b/i', $html) ?: 0;
        $blocks = preg_match_all('/<(?:p|div|li)\b/i', $html) ?: 0;

        return ($textLines($html) * $linePt) + ($heads * 3) + ($blocks * 0.6);
    };

    $atoms = [];
    $pushMasthead = static function (string $html, float $height) use (&$atoms): void {
        $atoms[] = ['kind' => 'html', 'html' => $html, 'h' => $height, 'keep' => false];
    };
    if ($isDraft) {
        $pushMasthead('<p class="provisional">PROVISÓRIA - EM CONFIRMAÇÃO</p>', $bodyPt + 2);
    }
    $pushMasthead('<p class="doc-title">Congregação Cristã no Brasil</p>', $titlePt + 1);
    $regionalHeading = trim((string) ($report->regional?->name ?? ''));
    if ($regionalHeading !== '') {
        $pushMasthead('<p class="doc-sub">'.e($regionalHeading).'</p>', $subPt + 0.5);
    }
    $pushMasthead(
        '<p class="doc-sub">Lista de Batismos e Diversos – '.e(\App\Services\BaptismListDocumentBlocks::issueDateLabel($report)).'</p>',
        $subPt + 3
    );
    $pushHtml = static function (string $html) use (&$atoms, $htmlHeight, $atomCap, $linePt): void {
        $html = trim($html);
        if ($html === '') {
            return;
        }
        $height = $htmlHeight($html);
        $hasHeading = (bool) preg_match('/<h[23]\b/i', $html);
        $keep = $hasHeading && $height <= ($linePt * 4);
        if ($height <= $atomCap || $keep) {
            $atoms[] = ['kind' => 'html', 'html' => $html, 'h' => $height, 'keep' => $keep];

            return;
        }
        $parts = preg_split('/(?<=<\/p>|<\/li>|<\/div>|<br>)/i', $html) ?: [$html];
        $buffer = '';
        $bufferHeight = 0.0;
        $flushBuffer = static function () use (&$atoms, &$buffer, &$bufferHeight, $htmlHeight, $linePt): void {
            if (trim($buffer) === '') {
                $buffer = '';
                $bufferHeight = 0.0;

                return;
            }
            $chunkHeight = max($bufferHeight, $htmlHeight($buffer));
            $keepChunk = (bool) preg_match('/<h[23]\b/i', $buffer) && $chunkHeight <= ($linePt * 4);
            $atoms[] = ['kind' => 'html', 'html' => $buffer, 'h' => $chunkHeight, 'keep' => $keepChunk];
            $buffer = '';
            $bufferHeight = 0.0;
        };
        foreach ($parts as $part) {
            if (trim($part) === '') {
                continue;
            }
            $partHeight = $htmlHeight($part);
            if ($buffer !== '' && ($bufferHeight + $partHeight) > $atomCap) {
                $flushBuffer();
            }
            $buffer .= $part;
            $bufferHeight += $partHeight;
        }
        $flushBuffer();
    };

    $sectionSerial = 0;
    foreach ($blocks as $block) {
        $html = $block['html'];
        if (preg_match('/^(.*?)(<table class="list">.*?<\/table>)(.*)$/s', $html, $match)) {
            $sectionSerial++;
            $lead = trim($match[1]);
            $heading = $lead;
            $preamble = '';
            if (preg_match('/^(.*<\/h[23]>)(.*)$/is', $lead, $leadParts)) {
                $heading = trim($leadParts[1]);
                $preamble = trim($leadParts[2]);
            }
            $headingHeight = $heading === '' ? 0.0 : $htmlHeight($heading);
            if ($heading !== '') {
                $atoms[] = [
                    'kind' => 'html',
                    'html' => $heading,
                    'h' => $headingHeight,
                    'keep' => $headingHeight <= ($linePt * 4),
                    'section' => $sectionSerial,
                ];
            }
            if ($preamble !== '') {
                $before = count($atoms);
                $pushHtml($preamble);
                for ($i = $before; $i < count($atoms); $i++) {
                    $atoms[$i]['section'] = $sectionSerial;
                }
            }
            preg_match_all('/<tr\b.*?<\/tr>/s', $match[2], $rowMatch);
            foreach ($rowMatch[0] as $row) {
                $atoms[] = [
                    'kind' => 'row',
                    'html' => $row,
                    // 0,88 aproxima a estimativa da altura real desenhada pelo DomPDF.
                    'h' => (max(1, $rowLines($row)) * $linePt) + $rowChrome,
                    'keep' => false,
                    'section' => $sectionSerial,
                    'resume' => $heading,
                    'resume_h' => $headingHeight,
                ];
            }
            $pushHtml($match[3]);

            continue;
        }
        $pushHtml($html);
    }

    $pushHtml('<p class="footer">'.\App\Services\BaptismListDocumentBlocks::CLOSING_NOTE.'</p>');

    $renderAtoms = static function (array $columnAtoms): string {
        $out = '';
        $open = false;
        foreach ($columnAtoms as $atom) {
            if (($atom['kind'] ?? '') === 'row') {
                if (! $open) {
                    $out .= '<table class="list">';
                    $open = true;
                }
                $out .= $atom['html'];

                continue;
            }
            if ($open) {
                $out .= '</table>';
                $open = false;
            }
            $out .= $atom['html'];
        }
        if ($open) {
            $out .= '</table>';
        }

        return $out;
    };

    $blankPage = static fn (): array => ['left' => [], 'right' => [], 'lh' => 0.0, 'rh' => 0.0];
    $pages = [];
    $page = $blankPage();
    $side = 'left';
    $isFirstPage = true;
    $usedHeight = static function () use (&$page, &$side): float {
        return $side === 'left' ? $page['lh'] : $page['rh'];
    };
    $capForPage = static function () use (&$isFirstPage, $firstCap, $nextCap): float {
        return $isFirstPage ? $firstCap : $nextCap;
    };
    $takeTrailingKeeps = static function () use (&$page, &$side): array {
        $bucket = $page[$side];
        $kept = [];
        while ($bucket !== []) {
            $last = $bucket[array_key_last($bucket)];
            if (empty($last['keep'])) {
                break;
            }
            array_pop($bucket);
            $kept[] = $last;
        }
        $page[$side] = $bucket;
        $sum = array_sum(array_column($bucket, 'h'));
        if ($side === 'left') {
            $page['lh'] = $sum;
        } else {
            $page['rh'] = $sum;
        }

        return array_reverse($kept);
    };
    $placeAtom = static function (array $atom) use (&$page, &$side): void {
        $page[$side][] = $atom;
        if ($side === 'left') {
            $page['lh'] += $atom['h'];
        } else {
            $page['rh'] += $atom['h'];
        }
    };

    // O Word não repete a faixa azul quando a tabela continua na outra coluna.
    $resumePrefix = static function (array $atom) use (&$page, &$side): ?array {
        return null;
    };

    foreach ($atoms as $atom) {
        $guard = 0;
        while (true) {
            $prefix = $resumePrefix($atom);
            $need = $atom['h'] + (float) ($prefix['h'] ?? 0);
            if (! ($usedHeight() > 0.5 && ($usedHeight() + $need) > ($capForPage() + 0.5))) {
                break;
            }
            $carry = $takeTrailingKeeps();
            if ($side === 'left') {
                $side = 'right';
            } else {
                $pages[] = $page;
                $page = $blankPage();
                $side = 'left';
                $isFirstPage = false;
            }
            foreach ($carry as $kept) {
                $placeAtom($kept);
            }
            if (++$guard > 8) {
                break;
            }
        }
        $prefix = $resumePrefix($atom);
        if ($prefix) {
            $placeAtom($prefix);
        }
        $placeAtom($atom);
    }
    if ($page['left'] !== [] || $page['right'] !== []) {
        $pages[] = $page;
    }
    if ($pages === []) {
        $pages[] = $blankPage();
    }

    // O Word enche a coluna esquerda e depois a direita. A última página não redistribui.
    $pageCount = count($pages);

    $versionLabel = \App\Services\BaptismListDocumentBlocks::versionLabel($report);
    $generatedLabel = \App\Services\BaptismListDocumentBlocks::generatedLabel($report);
@endphp

    @for ($page = 0; $page < $pageCount; $page++)
        <div class="sheet-page" @if ($page > 0) style="page-break-before: always;" @endif>
            @if ($page > 0)
                <p class="running">Lista de Batismos e Diversos – {{ $report->regional?->name }} – continua</p>
            @endif
            <table class="sheet">
                <tr>
                    <td class="col col-left">{!! $renderAtoms($pages[$page]['left']) !!}</td>
                    <td class="col col-right">{!! $renderAtoms($pages[$page]['right']) !!}</td>
                </tr>
            </table>
        </div>
    @endfor

    <div class="verso">
        <table>
            <tr>
                <td>Gerado em {{ $generatedLabel }} · {{ $versionLabel }}</td>
                <td class="mid">Pág. <span class="pagenum"></span></td>
                <td class="right">Vide o verso.</td>
            </tr>
        </table>
    </div>
</body>
</html>
