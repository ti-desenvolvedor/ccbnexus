<?php

namespace App\Support;

class SimpleRichText
{
    private const ALLOWED = '<b><strong><i><em><u><ul><ol><li><br><p><div>';

    public static function sanitize(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        $html = strip_tags($html, self::ALLOWED);
        $html = preg_replace('/<(\/?)(b|strong|i|em|u|ul|ol|li|p|div)\b[^>]*>/i', '<$1$2>', $html) ?? $html;
        $html = preg_replace('/<br\b[^>]*>/i', '<br>', $html) ?? $html;

        $plain = trim(html_entity_decode(strip_tags(str_replace(['<br>', '&nbsp;'], [' ', ' '], $html))));
        if ($plain === '') {
            return '';
        }

        return trim($html);
    }

    public static function containsMarkup(string $text): bool
    {
        return (bool) preg_match('/<\/?(?:b|strong|i|em|u|ul|ol|li|br|p|div)\b/i', $text);
    }

    public static function toHtml(string $text): string
    {
        if ($text === '') {
            return '';
        }

        if (! self::containsMarkup($text)) {
            return nl2br(e($text), false);
        }

        return self::sanitize($text);
    }
}
