<?php

namespace App\Services;

use App\Models\BaptismListReport;
use App\Models\BaptismListReportItem;
use App\Support\SimpleRichText;
use Illuminate\Support\Collection;

class BaptismListDocumentBlocks
{
    public const CLOSING_NOTE = 'Nas demais datas permanece a coleta única para atendimento da Obra da Piedade, Viagens, Manutenção e Construções.';

    /**
     * Secções visíveis na ordem do boletim (PDF e Word).
     *
     * @return array<string, Collection<int, BaptismListReportItem>>
     */
    public static function orderedSections(BaptismListReport $report): array
    {
        $report->loadMissing(['items', 'regional']);

        $sections = $report->items
            ->where('is_visible', true)
            ->groupBy('section_key')
            ->all();

        $ordered = [];
        foreach (array_keys(BaptismListReportBuilder::SECTION_ORDER) as $key) {
            if (isset($sections[$key])) {
                $ordered[$key] = $sections[$key];
            }
        }
        foreach ($sections as $key => $items) {
            if (! isset($ordered[$key])) {
                $ordered[$key] = $items;
            }
        }

        return $ordered;
    }

    public const DISPLAY_TIMEZONE = 'America/Sao_Paulo';

    public static function versionLabel(BaptismListReport $report): string
    {
        $revisionCode = 'R'.sprintf('%02d', (int) $report->revision);

        return match ($report->status) {
            BaptismListReport::STATUS_READY => 'Versão validada '.$revisionCode,
            BaptismListReport::STATUS_ARCHIVED => 'Versão validada '.$revisionCode.' (arquivada)',
            default => 'Versão '.$revisionCode.' (em confirmação)',
        };
    }

    public static function generatedLabel(BaptismListReport $report): string
    {
        $generatedAt = $report->generated_at ?? $report->edition_at;

        return ($generatedAt ?? now())->timezone(self::DISPLAY_TIMEZONE)->format('d/m/Y H:i');
    }

    public static function issueDateLabel(BaptismListReport $report): string
    {
        return $report->issue_date->locale('pt_BR')->translatedFormat('d \\d\\e F \\d\\e Y');
    }

    public static function librasIconPath(): string
    {
        return public_path('images/libras.png');
    }

    /**
     * Marcador do modelo REA: ícone das mãos ao lado do local.
     */
    public static function librasMarkerHtml(): string
    {
        static $html = null;
        if ($html !== null) {
            return $html;
        }

        $path = self::librasIconPath();
        if (! is_file($path)) {
            return $html = ' <em>(Libras)</em>';
        }

        $uri = 'data:image/png;base64,'.base64_encode((string) file_get_contents($path));

        return $html = ' <img src="'.$uri.'" alt="Libras" width="12" height="12" style="width: 11pt; height: 11pt; vertical-align: middle;">';
    }

    /**
     * Blocos do boletim. O HTML é o mesmo que o PDF pagina em colunas.
     *
     * @param  array<string, Collection<int, BaptismListReportItem>>  $sections
     * @return list<array<string, mixed>>
     */
    public static function build(BaptismListReport $report, array $sections): array
    {
        $titles = BaptismListReportBuilder::SECTION_TITLES;
        $listSections = BaptismListReportBuilder::LIST_SECTIONS;
        $narrativeSections = BaptismListReportBuilder::NARRATIVE_MEETING_SECTIONS;
        $introListSections = BaptismListReportBuilder::INTRO_LIST_MEETING_SECTIONS;
        $order = array_keys(BaptismListReportBuilder::SECTION_ORDER);
        $regionalName = BaptismListReportBuilder::regionalDisplayName($report->regional?->name);

        $blocks = [];
        $showedRegionalGroup = false;

        foreach ($order as $key) {
            $items = $sections[$key] ?? collect();
            if ($items->isEmpty()) {
                continue;
            }

            if ((in_array($key, $narrativeSections, true) || $key === 'reunioes') && ! $showedRegionalGroup) {
                $groupTitle = 'Reuniões Regionais'.($regionalName ? ' - '.$regionalName : '');
                $blocks[] = [
                    'type' => 'group',
                    'title' => $groupTitle,
                    'html' => '<h3 class="group">'.$groupTitle.'</h3>',
                    'weight' => 1,
                ];
                $showedRegionalGroup = true;
            }

            if ($key === 'mocidade') {
                $byMonth = $items->groupBy(fn ($i) => $i->payload['month_label'] ?? '');
                $firstMonth = self::monthFromPayload($items->first()->payload ?? []);
                foreach ($byMonth as $monthLabel => $monthItems) {
                    $sectionTitle = 'Reuniões da Mocidade'.($monthLabel ? ' – '.ucfirst((string) $monthLabel) : '');
                    $rows = self::tableRows($monthItems, $firstMonth, false);
                    $blocks[] = [
                        'type' => 'list',
                        'title' => $sectionTitle,
                        'rows' => $rows,
                        'note' => null,
                        'html' => '<h2 class="section">'.e($sectionTitle).'</h2><table class="list">'.implode('', array_column($rows, 'html')).'</table>',
                        'weight' => max(2, $monthItems->count()),
                    ];
                }

                continue;
            }

            $sectionTitle = $titles[$key] ?? $key;
            if ($key === 'obra-piedade' && $regionalName) {
                $sectionTitle = 'Reunião de Ensinamentos da Obra da Piedade na Regional '.$regionalName;
            }

            if ($key === 'reunioes') {
                foreach ($items as $item) {
                    $p = $item->payload ?? [];
                    $itemTitle = trim((string) ($p['title'] ?? ''));
                    if ($itemTitle === '') {
                        $itemTitle = 'Reunião';
                    }
                    $parts = [];
                    $html = '<h2 class="section">'.e($itemTitle).'</h2>';
                    $text = trim((string) ($p['description'] ?? ''));
                    if ($text !== '') {
                        if (SimpleRichText::containsMarkup($text)) {
                            $parts[] = ['kind' => 'html', 'html' => SimpleRichText::toHtml($text)];
                            $html .= '<div class="note">'.SimpleRichText::toHtml($text).'</div>';
                        } else {
                            $parts[] = ['kind' => 'plain', 'text' => $text];
                            $html .= '<p class="note">'.e($text).'</p>';
                        }
                    }
                    $blocks[] = [
                        'type' => 'narrative',
                        'title' => $itemTitle,
                        'parts' => $parts,
                        'html' => $html,
                        'weight' => 4,
                    ];
                }

                continue;
            }

            if (in_array($key, $listSections, true)) {
                $firstMonth = self::monthFromPayload($items->first()->payload ?? []);
                $rows = self::tableRows($items, $firstMonth, false);
                $note = ($key === 'ensaios' && $items->contains(fn ($i) => ! empty($i->payload['has_libras'])))
                    ? 'Com tradução simultânea em Libras (Língua Brasileira de Sinais)'
                    : null;
                $html = '<h2 class="section">'.e($sectionTitle).'</h2><table class="list">'.implode('', array_column($rows, 'html')).'</table>';
                if ($note) {
                    $html .= '<p class="note">'.e($note).'</p>';
                }
                $blocks[] = [
                    'type' => 'list',
                    'title' => $sectionTitle,
                    'rows' => $rows,
                    'note' => $note,
                    'html' => $html,
                    'weight' => max(2, $items->count()),
                ];
            } elseif (in_array($key, $narrativeSections, true)) {
                $parts = [];
                $html = '<h2 class="section">'.e($sectionTitle).'</h2>';
                foreach ($items as $item) {
                    $p = $item->payload ?? [];
                    $text = trim((string) ($p['description'] ?? ''));
                    if ($text === '') {
                        continue;
                    }
                    if (SimpleRichText::containsMarkup($text)) {
                        $parts[] = ['kind' => 'html', 'html' => SimpleRichText::toHtml($text)];
                        $html .= '<div class="note">'.SimpleRichText::toHtml($text).'</div>';
                    } elseif (str_contains($text, "\n•") || str_contains($text, "\n-")) {
                        $lines = preg_split("/\n+/", $text) ?: [];
                        $lead = (string) array_shift($lines);
                        $bullets = [];
                        $html .= '<p class="note">'.e($lead).'</p><ul class="slots">';
                        foreach ($lines as $line) {
                            $line = ltrim($line, "•-\t ");
                            if ($line === '') {
                                continue;
                            }
                            $bullets[] = $line;
                            $html .= '<li>'.e($line).'</li>';
                        }
                        $html .= '</ul>';
                        $parts[] = ['kind' => 'list', 'lead' => $lead, 'items' => $bullets];
                    } else {
                        $parts[] = ['kind' => 'plain', 'text' => $text];
                        $html .= '<p class="note">'.e($text).'</p>';
                    }
                }
                $blocks[] = [
                    'type' => 'narrative',
                    'title' => $sectionTitle,
                    'parts' => $parts,
                    'html' => $html,
                    'weight' => 4,
                ];
            } elseif (in_array($key, $introListSections, true)) {
                $intro = collect($items)->map(fn ($i) => $i->payload['audience_intro'] ?? null)->filter()->first()
                    ?? BaptismListReportBuilder::DEFAULT_AUDIENCE_INTROS[$key]
                    ?? null;
                $firstMonth = self::monthFromPayload($items->first()->payload ?? []);
                $rows = self::tableRows($items, $firstMonth, true);
                $html = '<h2 class="section">'.e($sectionTitle).'</h2>';
                if ($intro) {
                    $html .= '<p class="intro">'.e($intro).'</p>';
                }
                $html .= '<table class="list">'.implode('', array_column($rows, 'html')).'</table>';
                $blocks[] = [
                    'type' => 'intro_list',
                    'title' => $sectionTitle,
                    'intro' => $intro,
                    'rows' => $rows,
                    'html' => $html,
                    'weight' => max(3, $items->count() + 2),
                ];
            } else {
                $textItems = [];
                $html = '';
                foreach ($items as $item) {
                    $p = $item->payload ?? [];
                    $title = trim((string) ($p['title'] ?? ''));
                    $body = trim((string) ($p['body'] ?? $p['description'] ?? ''));
                    $mode = ! empty($p['mode']) ? (string) $p['mode'] : '';
                    $collectionOn = ! empty($p['collection_on']) ? (string) $p['collection_on'] : '';
                    if ($title === '' && $body === '') {
                        continue;
                    }
                    $textItems[] = [
                        'title' => $title,
                        'mode' => $mode,
                        'collection_on' => $collectionOn,
                        'body' => $body,
                        'body_html' => $body !== '' && SimpleRichText::containsMarkup($body),
                    ];
                    if ($title !== '') {
                        $html .= '<p class="note-title">'.e($title).'</p>';
                    }
                    if ($body !== '') {
                        $html .= '<p class="note">';
                        if ($mode !== '') {
                            $html .= '('.e($mode).') ';
                        }
                        if ($collectionOn !== '') {
                            $html .= e($collectionOn).' — ';
                        }
                        $html .= ($textItems[array_key_last($textItems)]['body_html']
                            ? SimpleRichText::toHtml($body)
                            : e($body)).'</p>';
                    }
                }
                if ($html !== '') {
                    $blocks[] = [
                        'type' => 'text',
                        'items' => $textItems,
                        'html' => $html,
                        'weight' => max(3, $items->count() * 2),
                    ];
                }
            }
        }

        return $blocks;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function monthFromPayload(array $payload): ?int
    {
        $date = (string) ($payload['date'] ?? '');
        if (preg_match('#^\d{1,2}/(\d{1,2})#', $date, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    /**
     * @param  iterable<int, BaptismListReportItem>  $items
     * @return list<array<string, mixed>>
     */
    private static function tableRows(iterable $items, ?int $firstMonth, bool $wide): array
    {
        $rows = [];
        foreach ($items as $item) {
            $p = $item->payload ?? [];
            $month = self::monthFromPayload($p);
            $alt = (bool) ($firstMonth && $month && $month !== $firstMonth);
            $location = (string) ($p['location_label'] ?? $p['title'] ?? '');
            $date = (string) ($p['date'] ?? '');
            $weekday = (string) ($p['weekday'] ?? '');
            $time = (string) ($p['time'] ?? '');
            $attendant = (string) ($p['attendant'] ?? '');
            $libras = ! empty($p['has_libras']);
            $altAttr = $alt ? ' class="alt"' : '';
            $locClass = $wide ? 'loc-wide' : 'loc';
            $html = '<tr'.$altAttr.'>'
                .'<td class="date">'.e($date).'</td>'
                .'<td class="day">'.e($weekday).'</td>'
                .'<td class="time">'.e($time).'</td>'
                .'<td class="'.$locClass.'">'.BaptismListReportBuilder::pdfLocationHtml($location)
                .(! $wide && $libras ? self::librasMarkerHtml() : '')
                .'</td>';
            if (! $wide) {
                $html .= '<td class="att">'.e($attendant).'</td>';
            }
            $html .= '</tr>';
            $rows[] = [
                'date' => $date,
                'weekday' => $weekday,
                'time' => $time,
                'location' => $location,
                'attendant' => $attendant,
                'libras' => $libras,
                'alt' => $alt,
                'wide' => $wide,
                'html' => $html,
            ];
        }

        return $rows;
    }
}
