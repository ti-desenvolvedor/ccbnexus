<?php

namespace App\Services;

use App\Models\Event;
use App\Models\WhatsAppNoticeTemplate;
use Carbon\Carbon;

class WhatsAppNoticeRenderer
{
    /**
     * @param  array<string, string|null>  $overrides
     */
    public function renderForEvent(Event $event, WhatsAppNoticeTemplate $template, array $overrides = []): string
    {
        $startsAt = $event->starts_at instanceof Carbon ? $event->starts_at : Carbon::parse($event->starts_at);
        $weekday = $startsAt->locale('pt_BR')->translatedFormat('l');

        $location = $overrides['location'] ?? $event->location?->name ?? '';
        $dressCode = $this->dressCodeLabel($overrides['dress_code'] ?? $event->dress_code);

        $replace = [
            '{event_title}' => (string) ($overrides['event_title'] ?? $event->title),
            '{event_type}' => (string) ($overrides['event_type'] ?? $event->eventType?->name ?? ''),
            '{weekday}' => (string) ($overrides['weekday'] ?? $weekday),
            '{date}' => (string) ($overrides['date'] ?? $startsAt->format('d/m/Y')),
            '{time}' => (string) ($overrides['time'] ?? $startsAt->format('H:i')),
            '{location}' => (string) ($location ?: '—'),
            '{location_hybrid}' => (string) ($overrides['location_hybrid'] ?? 'Será transmitida a partir da Administração responsável para as sedes vinculadas.'),
            '{dress_code}' => (string) ($overrides['dress_code_label'] ?? $dressCode),
            '{audience_text}' => (string) ($overrides['audience_text'] ?? ''),
            '{notes}' => (string) ($overrides['notes'] ?? ''),
            '{link}' => (string) ($overrides['link'] ?? ''),
            '{signature}' => (string) ($overrides['signature'] ?? ''),
        ];

        $text = strtr($template->body, $replace);

        // Limpeza leve: evita placeholders vazios com linhas excessivas.
        $text = preg_replace("/\\n{3,}/", "\n\n", trim($text)) ?? trim($text);

        return $text;
    }

    private function dressCodeLabel(?string $code): string
    {
        return match ($code) {
            'social' => 'Social',
            'esporte_fino' => 'Esporte fino',
            default => '—',
        };
    }
}

