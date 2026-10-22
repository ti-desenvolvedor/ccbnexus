<?php

namespace App\Services;

/**
 * Medidas partilhadas do boletim (Word e PDF).
 * Uma alteração aqui tem de aparecer nos dois formatos.
 */
final class BaptismListDocumentLayout
{
    public const FONT = 'Arial Narrow';

    public const BODY_PT = 8.5;

    public const HEADING_PT = 9.0;

    public const TITLE_PT = 12.0;

    public const SUB_PT = 10.0;

    public const META_PT = 7.5;

    public const LINE_HEIGHT = 1.0;

    /** Altura de uma linha de tabela no DomPDF, alinhada à entrelinha simples do Word. */
    public const NARROW_LINE_PT = 10.1;

    public const PAGE_WIDTH_CM = 21.0;

    public const PAGE_HEIGHT_CM = 29.7;

    public const MARGIN_TOP_CM = 0.4;

    public const MARGIN_BOTTOM_CM = 0.85;

    public const MARGIN_SIDE_CM = 0.55;

    public const MARGIN_HEADER_CM = 0.15;

    public const MARGIN_FOOTER_CM = 0.15;

    public const COLUMN_GAP_CM = 0.35;

    public static function marginCss(): string
    {
        return sprintf(
            'margin: %smm %smm %smm %smm;',
            self::mm(self::MARGIN_TOP_CM),
            self::mm(self::MARGIN_SIDE_CM),
            self::mm(self::MARGIN_BOTTOM_CM),
            self::mm(self::MARGIN_SIDE_CM),
        );
    }

    public static function columnPaddingMm(): float
    {
        return self::MARGIN_SIDE_CM > 0 ? (self::COLUMN_GAP_CM * 10) / 2 : 0;
    }

    private static function mm(float $cm): string
    {
        $mm = $cm * 10;

        return rtrim(rtrim(number_format($mm, 1, '.', ''), '0'), '.');
    }
}
