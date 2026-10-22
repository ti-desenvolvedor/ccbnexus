<?php

namespace App\Services;

use App\Models\BaptismListReport;
use App\Support\SimpleRichText;
use DOMDocument;
use DOMElement;
use DOMNode;
use PhpOffice\PhpWord\Element\Footer;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWord\Style\Language;
use PhpOffice\PhpWord\Style\Table;

class BaptismListDocxExporter
{
    private int $columnWidth;

    public function __construct()
    {
        $page = (int) Converter::cmToTwip(BaptismListDocumentLayout::PAGE_WIDTH_CM);
        $side = (int) Converter::cmToTwip(BaptismListDocumentLayout::MARGIN_SIDE_CM);
        $gap = (int) Converter::cmToTwip(BaptismListDocumentLayout::COLUMN_GAP_CM);
        $this->columnWidth = (int) floor(($page - (2 * $side) - $gap) / 2);
    }

    /**
     * @param  array<string, mixed>  $sections
     */
    public function binary(BaptismListReport $report, array $sections): string
    {
        $writer = IOFactory::createWriter($this->document($report, $sections), 'Word2007');
        ob_start();
        $writer->save('php://output');

        return (string) ob_get_clean();
    }

    /**
     * @param  array<string, mixed>  $sections
     */
    public function document(BaptismListReport $report, array $sections): PhpWord
    {
        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName(BaptismListDocumentLayout::FONT);
        $phpWord->setDefaultFontSize(BaptismListDocumentLayout::BODY_PT);
        $phpWord->setDefaultParagraphStyle($this->paragraph());
        $phpWord->getSettings()->setThemeFontLang(new Language('pt-BR'));

        $page = [
            'pageSizeW' => Converter::cmToTwip(BaptismListDocumentLayout::PAGE_WIDTH_CM),
            'pageSizeH' => Converter::cmToTwip(BaptismListDocumentLayout::PAGE_HEIGHT_CM),
            'marginTop' => Converter::cmToTwip(BaptismListDocumentLayout::MARGIN_TOP_CM),
            'marginBottom' => Converter::cmToTwip(BaptismListDocumentLayout::MARGIN_BOTTOM_CM),
            'marginLeft' => Converter::cmToTwip(BaptismListDocumentLayout::MARGIN_SIDE_CM),
            'marginRight' => Converter::cmToTwip(BaptismListDocumentLayout::MARGIN_SIDE_CM),
            'marginHeader' => Converter::cmToTwip(BaptismListDocumentLayout::MARGIN_HEADER_CM),
            'marginFooter' => Converter::cmToTwip(BaptismListDocumentLayout::MARGIN_FOOTER_CM),
        ];

        $body = $phpWord->addSection($page + [
            'colsNum' => 2,
            'colsSpace' => Converter::cmToTwip(BaptismListDocumentLayout::COLUMN_GAP_CM),
        ]);
        $this->addContinuationHeader($body, $report);
        $this->addFooter($body, $report);
        $this->addMasthead($body, $report);

        foreach (BaptismListDocumentBlocks::build($report, $sections) as $block) {
            $this->addBlock($body, $block);
        }

        $body->addText(
            BaptismListDocumentBlocks::CLOSING_NOTE,
            $this->font(),
            $this->paragraph(['alignment' => 'both', 'spaceBefore' => 80])
        );

        return $phpWord;
    }

    private function addMasthead(Section $section, BaptismListReport $report): void
    {
        $isDraft = ($report->status ?? null) === BaptismListReport::STATUS_DRAFT;
        if ($isDraft) {
            $section->addText(
                'PROVISÓRIA - EM CONFIRMAÇÃO',
                $this->font(BaptismListDocumentLayout::BODY_PT, true, '006600'),
                $this->paragraph(['alignment' => 'center', 'spaceAfter' => 40])
            );
        }

        $section->addText(
            'Congregação Cristã no Brasil',
            $this->font(BaptismListDocumentLayout::TITLE_PT, true),
            $this->paragraph(['alignment' => 'center'])
        );
        $regional = trim((string) ($report->regional?->name ?? ''));
        if ($regional !== '') {
            $section->addText($regional, $this->font(BaptismListDocumentLayout::SUB_PT, true), $this->paragraph(['alignment' => 'center']));
        }
        $section->addText(
            'Lista de Batismos e Diversos – '.BaptismListDocumentBlocks::issueDateLabel($report),
            $this->font(BaptismListDocumentLayout::SUB_PT, true),
            $this->paragraph(['alignment' => 'center', 'spaceAfter' => 40])
        );
    }

    private function addContinuationHeader(Section $section, BaptismListReport $report): void
    {
        $regional = trim((string) ($report->regional?->name ?? ''));
        $section->addHeader(Footer::FIRST);
        $section->addHeader()->addText(
            'Lista de Batismos e Diversos – '.$regional.' – continua',
            $this->font(BaptismListDocumentLayout::META_PT, true),
            $this->paragraph(['alignment' => 'center', 'spaceAfter' => 60])
        );
    }

    private function addFooter(Section $section, BaptismListReport $report): void
    {
        $footer = $section->addFooter();
        $usable = (int) Converter::cmToTwip(BaptismListDocumentLayout::PAGE_WIDTH_CM)
            - (2 * (int) Converter::cmToTwip(BaptismListDocumentLayout::MARGIN_SIDE_CM));
        $left = (int) round($usable * 0.50);
        $mid = (int) round($usable * 0.22);
        $right = $usable - $left - $mid;
        $font = $this->font(BaptismListDocumentLayout::META_PT);
        $table = $footer->addTable([
            'layout' => Table::LAYOUT_FIXED,
            'width' => $usable,
            'unit' => TblWidth::TWIP,
            'borderTopSize' => 4,
            'borderTopColor' => '000000',
        ]);
        $table->addRow();
        $table->addCell($left)->addText(
            'Gerado em '.BaptismListDocumentBlocks::generatedLabel($report).' · '.BaptismListDocumentBlocks::versionLabel($report),
            $font,
            $this->paragraph()
        );
        $table->addCell($mid)->addPreserveText(
            'Pág. {PAGE}',
            $font,
            $this->paragraph(['alignment' => 'center'])
        );
        $table->addCell($right)->addText(
            'Vide o verso.',
            $font,
            $this->paragraph(['alignment' => 'right'])
        );
    }

    /**
     * @param  array<string, mixed>  $block
     */
    private function addBlock(Section $section, array $block): void
    {
        $type = $block['type'] ?? '';
        if (in_array($type, ['group', 'list', 'narrative', 'intro_list'], true)) {
            $this->addBanner($section, (string) ($block['title'] ?? ''));
        }

        if ($type === 'list') {
            $this->addDataTable($section, $block['rows'] ?? [], false);
            if (! empty($block['note'])) {
                $section->addText((string) $block['note'], $this->font(), $this->paragraph(['spaceBefore' => 40]));
            }

            return;
        }

        if ($type === 'narrative') {
            foreach ($block['parts'] ?? [] as $part) {
                $this->addPart($section, $part);
            }

            return;
        }

        if ($type === 'intro_list') {
            if (! empty($block['intro'])) {
                $section->addText(
                    (string) $block['intro'],
                    $this->font(),
                    $this->paragraph(['alignment' => 'both', 'spaceBefore' => 40, 'spaceAfter' => 40])
                );
            }
            $this->addDataTable($section, $block['rows'] ?? [], true);

            return;
        }

        if ($type === 'text') {
            foreach ($block['items'] ?? [] as $item) {
                if (($item['title'] ?? '') !== '') {
                    $section->addText(
                        (string) $item['title'],
                        $this->font(BaptismListDocumentLayout::HEADING_PT, true),
                        $this->paragraph(['alignment' => 'center', 'spaceBefore' => 60])
                    );
                }
                if (($item['body'] ?? '') === '') {
                    continue;
                }
                $prefix = '';
                if (($item['mode'] ?? '') !== '') {
                    $prefix .= '('.$item['mode'].') ';
                }
                if (($item['collection_on'] ?? '') !== '') {
                    $prefix .= $item['collection_on'].' — ';
                }
                if (! empty($item['body_html'])) {
                    $this->writeHtml($section, $prefix.SimpleRichText::toHtml((string) $item['body']));
                } else {
                    $section->addText($prefix.$item['body'], $this->font(), $this->paragraph(['alignment' => 'both']));
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $part
     */
    private function addPart(Section $section, array $part): void
    {
        $kind = $part['kind'] ?? 'plain';
        if ($kind === 'html') {
            $this->writeHtml($section, (string) ($part['html'] ?? ''));

            return;
        }
        if ($kind === 'list') {
            if (($part['lead'] ?? '') !== '') {
                $section->addText((string) $part['lead'], $this->font(), $this->paragraph(['alignment' => 'both']));
            }
            foreach ($part['items'] ?? [] as $line) {
                $section->addText('• '.$line, $this->font(), $this->paragraph(['alignment' => 'both']));
            }

            return;
        }

        $text = (string) ($part['text'] ?? '');
        if ($text === '') {
            return;
        }
        foreach (preg_split("/\n+/", $text) ?: [] as $line) {
            if ($line === '') {
                continue;
            }
            $section->addText($line, $this->font(), $this->paragraph(['alignment' => 'both']));
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function addDataTable(Section $section, array $rows, bool $wide): void
    {
        if ($rows === []) {
            return;
        }

        $ratios = $wide ? [0.16, 0.13, 0.17, 0.54] : [0.16, 0.13, 0.17, 0.32, 0.22];
        $widths = $this->splitWidth($this->columnWidth, $ratios);
        $table = $section->addTable([
            'layout' => Table::LAYOUT_FIXED,
            'width' => $this->columnWidth,
            'unit' => TblWidth::TWIP,
            'cellMargin' => 6,
        ]);

        foreach ($rows as $row) {
            $table->addRow(null, ['cantSplit' => true]);
            $alt = (bool) ($row['alt'] ?? false);
            $this->addPlainCell($table, $widths[0], (string) ($row['date'] ?? ''), $alt, 'center');
            $this->addPlainCell($table, $widths[1], (string) ($row['weekday'] ?? ''), $alt, 'center');
            $this->addPlainCell($table, $widths[2], (string) ($row['time'] ?? ''), $alt, 'center');
            $this->addLocationCell($table, $widths[3], $row, $alt);
            if (! $wide) {
                $this->addPlainCell($table, $widths[4], (string) ($row['attendant'] ?? ''), $alt, 'center');
            }
        }
    }

    private function addBanner(Section $section, string $text): void
    {
        $table = $section->addTable([
            'layout' => Table::LAYOUT_FIXED,
            'width' => $this->columnWidth,
            'unit' => TblWidth::TWIP,
            'cellMargin' => 6,
        ]);
        $table->addRow();
        $cell = $table->addCell($this->columnWidth, [
            'bgColor' => 'B3DDF2',
            'borderSize' => 4,
            'borderColor' => '000000',
            'valign' => 'center',
        ]);
        $cell->addText($text, $this->font(BaptismListDocumentLayout::HEADING_PT, true), $this->paragraph([
            'alignment' => 'center',
            'spaceBefore' => 20,
            'spaceAfter' => 20,
        ]));
    }

    /**
     * @param  \PhpOffice\PhpWord\Element\Table  $table
     */
    private function addPlainCell($table, int $width, string $text, bool $alt, string $align): void
    {
        $cell = $table->addCell($width, $this->cellStyle($alt));
        $cell->addText($text, $this->font(), $this->paragraph(['alignment' => $align]));
    }

    /**
     * @param  \PhpOffice\PhpWord\Element\Table  $table
     * @param  array<string, mixed>  $row
     */
    private function addLocationCell($table, int $width, array $row, bool $alt): void
    {
        $cell = $table->addCell($width, $this->cellStyle($alt));
        $run = $cell->addTextRun($this->paragraph());
        $lines = BaptismListReportBuilder::locationLines((string) ($row['location'] ?? ''));
        if ($lines === []) {
            $run->addText('', $this->font());
        }
        foreach ($lines as $index => $line) {
            if ($index > 0) {
                $run->addTextBreak();
            }
            $run->addText(str_replace('-', "\u{2011}", $line), $this->font(BaptismListDocumentLayout::BODY_PT));
        }
        if (empty($row['libras']) || ! empty($row['wide'])) {
            return;
        }

        $icon = BaptismListDocumentBlocks::librasIconPath();
        if (! is_file($icon)) {
            $run->addText(' (Libras)', $this->font(BaptismListDocumentLayout::BODY_PT, false, null, true));

            return;
        }

        $run->addText(' ', $this->font());
        $run->addImage($icon, [
            'width' => 14,
            'height' => 14,
            'wrappingStyle' => 'inline',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function cellStyle(bool $alt): array
    {
        $style = [
            'borderSize' => 4,
            'borderColor' => '000000',
            'valign' => 'center',
        ];
        if ($alt) {
            $style['bgColor'] = 'D9D9D9';
        }

        return $style;
    }

    /**
     * @param  list<float>  $ratios
     * @return list<int>
     */
    private function splitWidth(int $total, array $ratios): array
    {
        $widths = [];
        $used = 0;
        $last = count($ratios) - 1;
        foreach ($ratios as $index => $ratio) {
            if ($index === $last) {
                $widths[] = $total - $used;

                break;
            }
            $width = (int) round($total * $ratio);
            $widths[] = $width;
            $used += $width;
        }

        return $widths;
    }

    private function writeHtml(Section $section, string $html): void
    {
        $html = trim($html);
        if ($html === '') {
            return;
        }

        $dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><html><body>'.$html.'</body></html>');
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $body = $dom->getElementsByTagName('body')->item(0);
        if (! $body instanceof DOMElement) {
            $section->addText(trim(html_entity_decode(strip_tags($html))), $this->font(), $this->paragraph(['alignment' => 'both']));

            return;
        }

        $this->writeBlockNodes($section, $body);
    }

    private function writeBlockNodes(Section $section, DOMNode $node): void
    {
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $text = trim($child->textContent ?? '');
                if ($text !== '') {
                    $section->addText($text, $this->font(), $this->paragraph(['alignment' => 'both']));
                }

                continue;
            }
            if (! $child instanceof DOMElement) {
                continue;
            }
            $tag = strtolower($child->nodeName);
            if ($tag === 'ul' || $tag === 'ol') {
                foreach ($child->childNodes as $item) {
                    if (! $item instanceof DOMElement || strtolower($item->nodeName) !== 'li') {
                        continue;
                    }
                    $run = $section->addTextRun($this->paragraph(['alignment' => 'both']));
                    $run->addText('• ', $this->font());
                    $this->appendInline($run, $item, []);
                }

                continue;
            }
            if ($tag === 'br') {
                continue;
            }
            $run = $section->addTextRun($this->paragraph(['alignment' => 'both']));
            $this->appendInline($run, $child, []);
        }
    }

    /**
     * @param  TextRun  $run
     * @param  array<string, mixed>  $style
     */
    private function appendInline($run, DOMNode $node, array $style): void
    {
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $text = $child->textContent ?? '';
                if ($text !== '') {
                    $run->addText($text, $this->font(BaptismListDocumentLayout::BODY_PT, (bool) ($style['bold'] ?? false), null, (bool) ($style['italic'] ?? false), (bool) ($style['underline'] ?? false)));
                }

                continue;
            }
            if (! $child instanceof DOMElement) {
                continue;
            }
            $tag = strtolower($child->nodeName);
            if ($tag === 'br') {
                $run->addTextBreak();

                continue;
            }
            $next = $style;
            if ($tag === 'b' || $tag === 'strong') {
                $next['bold'] = true;
            } elseif ($tag === 'i' || $tag === 'em') {
                $next['italic'] = true;
            } elseif ($tag === 'u') {
                $next['underline'] = true;
            }
            $this->appendInline($run, $child, $next);
        }
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function paragraph(array $extra = []): array
    {
        return $extra + [
            'spaceBefore' => 0,
            'spaceAfter' => 0,
            // PhpWord soma 240 ao valor de spacing quando a regra é auto.
            // lineHeight 1 vira w:line="240" (entrelinha simples).
            'lineHeight' => BaptismListDocumentLayout::LINE_HEIGHT,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function font(float $size = BaptismListDocumentLayout::BODY_PT, bool $bold = false, ?string $color = null, bool $italic = false, bool $underline = false): array
    {
        $font = [
            'name' => BaptismListDocumentLayout::FONT,
            'size' => $size,
        ];
        if ($bold) {
            $font['bold'] = true;
        }
        if ($italic) {
            $font['italic'] = true;
        }
        if ($underline) {
            $font['underline'] = 'single';
        }
        if ($color) {
            $font['color'] = $color;
        }

        return $font;
    }
}
