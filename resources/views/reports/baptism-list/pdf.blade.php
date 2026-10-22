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
    $draftPt = (($report->status ?? null) === \App\Models\BaptismListReport::STATUS_DRAFT)
        ? (($bodyPt * $lineHeight) + 4)
        : 0;
    $mastheadPt = $draftPt + ($titlePt * $lineHeight) + (2 * $subPt * $lineHeight) + 4;
    $runningPt = ($metaPt * $lineHeight) + 4;
    $columnHeightFirst = max(160, $pageHeightPt - $mastheadPt);
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

        .masthead {
            text-align: center;
            margin: 0 0 2px;
        }
        .masthead h1 {
            font-size: {{ $titlePt }}pt;
            font-weight: bold;
            margin: 0;
        }
        .masthead .sub {
            font-size: {{ $subPt }}pt;
            font-weight: bold;
            margin: 0;
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
            margin: 2px 0 1px;
            padding: 0.8pt 2px;
            page-break-after: avoid;
            page-break-inside: avoid;
        }

        h3.group {
            font-size: {{ $headingPt }}pt;
            font-weight: bold;
            text-align: center;
            background: #B3DDF2;
            border: 0.5pt solid #000;
            margin: 3px 0 1px;
            padding: 0.8pt 2px;
            page-break-after: avoid;
            page-break-inside: avoid;
        }

        table.list {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 2px;
            font-size: {{ $bodyPt }}pt;
            table-layout: fixed;
        }
        table.list tr { page-break-inside: avoid; }
        table.list td {
            border: 0.4pt solid #000;
            vertical-align: middle;
            padding: 0.6pt 1.5pt;
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
            margin: 1px 0 3px;
            font-size: {{ $bodyPt }}pt;
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
        .note ul { margin: 2px 0 4px 14px; padding: 0; }
        .note li { margin-bottom: 1px; }

        p { margin: 0; }
        .footer {
            margin: 4px 0 0;
            font-size: {{ $bodyPt }}pt;
            text-align: justify;
        }
        .verso {
            position: fixed;
            bottom: -{{ max(6, $marginBottomMm - 6) }}mm;
            left: 0;
            width: 100%;
            height: 7mm;
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

    // Passo medido no DomPDF com Arial Narrow no corpo atual.
    // A coluna para quando a fileira seguinte não cabe.
    $linePt = $useNarrow ? $layout::NARROW_LINE_PT : ($bodyPt * 1.35);
    $rowChrome = 2.8;
    $charPt = $bodyPt * ($useNarrow ? 0.46 : 0.56);
    $charsPerLine = max(22, (int) floor($columnInnerPt / $charPt));
    $firstCap = $columnHeightFirst - 6;
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

        return ($textLines($html) * $linePt) + ($heads * 12) + ($blocks * 2.5) + 2;
    };

    $atoms = [];
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
                    'h' => ((max(1, $rowLines($row)) * $linePt) + $rowChrome) * 0.88,
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

    $resumePrefix = static function (array $atom) use (&$page, &$side): ?array {
        $resume = (string) ($atom['resume'] ?? '');
        $section = $atom['section'] ?? null;
        if ($resume === '' || $section === null) {
            return null;
        }
        $bucket = $page[$side];
        if ($bucket !== []) {
            $last = $bucket[array_key_last($bucket)];
            if (($last['section'] ?? null) === $section) {
                return null;
            }
        }

        return [
            'kind' => 'html',
            'html' => $resume,
            'h' => (float) ($atom['resume_h'] ?? 0),
            'keep' => true,
            'section' => $section,
        ];
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

    // Na última página o Word equilibra as duas colunas.
    $lastIndex = count($pages) - 1;
    $last = $pages[$lastIndex];
    $all = array_merge($last['left'], $last['right']);
    $totalHeight = array_sum(array_column($all, 'h'));
    $pageCap = $lastIndex === 0 ? $firstCap : $nextCap;
    if (count($all) > 1 && $totalHeight > 0 && $totalHeight <= ($pageCap * 2)) {
        $target = $totalHeight / 2;
        $left = [];
        $leftHeight = 0.0;
        $cut = 0;
        foreach ($all as $index => $atom) {
            $nextHeight = $leftHeight + $atom['h'];
            $pastTarget = $left !== [] && $nextHeight > $target && $leftHeight >= ($target * 0.72);
            $pastCap = $left !== [] && $nextHeight > $pageCap;
            if ($pastTarget || $pastCap) {
                $cut = $index;
                break;
            }
            $left[] = $atom;
            $leftHeight = $nextHeight;
            $cut = $index + 1;
        }
        while ($cut > 0 && $cut < count($all) && ! empty($all[$cut - 1]['keep'])) {
            array_pop($left);
            $leftHeight -= $all[$cut - 1]['h'];
            $cut--;
        }
        $right = array_slice($all, $cut);
        $withResume = static function (array $columnAtoms): array {
            $out = [];
            $current = null;
            foreach ($columnAtoms as $atom) {
                $section = $atom['section'] ?? null;
                $resume = (string) ($atom['resume'] ?? '');
                if ($section !== null && $section !== $current && $resume !== '') {
                    $last = $out === [] ? null : $out[array_key_last($out)];
                    if (! $last || ($last['html'] ?? '') !== $resume) {
                        $out[] = [
                            'kind' => 'html',
                            'html' => $resume,
                            'h' => (float) ($atom['resume_h'] ?? 0),
                            'keep' => true,
                            'section' => $section,
                        ];
                    }
                }
                $out[] = $atom;
                if ($section !== null) {
                    $current = $section;
                }
            }

            return $out;
        };
        $left = $withResume($left);
        $right = $withResume($right);
        $leftHeight = array_sum(array_column($left, 'h'));
        $rightHeight = array_sum(array_column($right, 'h'));
        if ($left !== [] && $right !== [] && $leftHeight <= ($pageCap + 0.5) && $rightHeight <= ($pageCap + 0.5)) {
            $pages[$lastIndex] = [
                'left' => $left,
                'right' => $right,
                'lh' => $leftHeight,
                'rh' => $rightHeight,
            ];
        }
    }

    $pageCount = count($pages);

    $versionLabel = \App\Services\BaptismListDocumentBlocks::versionLabel($report);
    $generatedLabel = \App\Services\BaptismListDocumentBlocks::generatedLabel($report);
@endphp

    @if ($isDraft)
        <p class="provisional">PROVISÓRIA - EM CONFIRMAÇÃO</p>
    @endif

    <div class="masthead">
        <h1>Congregação Cristã no Brasil</h1>
        <p class="sub">{{ $report->regional?->name }}</p>
        <p class="sub">Lista de Batismos e Diversos – {{ $report->issue_date->locale('pt_BR')->translatedFormat('d \\d\\e F \\d\\e Y') }}</p>
    </div>

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
