<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventType;
use App\Models\EventTypeAudiencePreset;
use App\Models\PublicPosition;
use App\Support\SimpleRichText;
use Carbon\Carbon;

class EventTypeAudiencePresetService
{
    /**
     * @return list<array{token: string, label: string}>
     */
    public function placeholderCatalog(): array
    {
        return [
            ['token' => '{day_month}', 'label' => 'Data (dia/mês)'],
            ['token' => '{date}', 'label' => 'Data completa'],
            ['token' => '{date_long}', 'label' => 'Data por extenso'],
            ['token' => '{weekday}', 'label' => 'Dia da semana'],
            ['token' => '{time}', 'label' => 'Hora de início'],
            ['token' => '{end_time}', 'label' => 'Hora de término'],
            ['token' => '{location}', 'label' => 'Local'],
            ['token' => '{regional}', 'label' => 'Regional'],
            ['token' => '{title}', 'label' => 'Título do evento'],
        ];
    }

    public function saveParagraph(int $eventTypeId, string $paragraph, ?string $layout = null): EventTypeAudiencePreset
    {
        $paragraph = SimpleRichText::sanitize($paragraph);
        $previous = (string) (EventTypeAudiencePreset::query()
            ->where('event_type_id', $eventTypeId)
            ->value('invitation_paragraph') ?? '');

        $attributes = [
            'invitation_paragraph' => $paragraph,
        ];
        if ($layout !== null && $layout !== '') {
            $attributes['report_layout'] = $layout;
        }

        $preset = EventTypeAudiencePreset::query()->updateOrCreate(
            ['event_type_id' => $eventTypeId],
            $attributes,
        );

        $this->replaceMatchingDescriptions($eventTypeId, $previous, $paragraph);

        return $preset;
    }

    /**
     * Eventos que ainda usam o texto anterior do modelo passam a usar o texto novo.
     * Uma descrição editada à mão fica como está.
     */
    public function replaceMatchingDescriptions(int $eventTypeId, string $previous, string $next): void
    {
        $previous = trim($previous);
        $next = trim($next);
        if ($previous === '' || $previous === $next) {
            return;
        }

        Event::query()
            ->where('event_type_id', $eventTypeId)
            ->where('description', $previous)
            ->update(['description' => $next]);
    }

    public function presetForType(?int $eventTypeId): ?EventTypeAudiencePreset
    {
        if (! $eventTypeId) {
            return null;
        }

        return EventTypeAudiencePreset::query()
            ->with('positions')
            ->where('event_type_id', $eventTypeId)
            ->first();
    }

    /**
     * @return list<int>
     */
    public function resolvePositionIds(EventTypeAudiencePreset $preset, int $regionalId): array
    {
        $slugs = $preset->positions->pluck('position_slug')->filter()->all();
        if ($slugs === []) {
            return [];
        }

        return PublicPosition::query()
            ->whereIn('slug', $slugs)
            ->where(function ($q) use ($regionalId) {
                $q->whereHas('department', fn ($d) => $d->where('regional_id', $regionalId))
                    ->orWhereHas('subgroup.group', fn ($g) => $g->where('regional_id', $regionalId));
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function containsPlaceholder(string $text): bool
    {
        foreach ($this->placeholderCatalog() as $field) {
            if (str_contains($text, $field['token'])) {
                return true;
            }
        }

        return false;
    }

    public function renderParagraph(EventTypeAudiencePreset $preset, Event $event): string
    {
        return $this->renderTemplate((string) ($preset->invitation_paragraph ?? ''), $event);
    }

    public function renderTemplate(string $template, Event $event): string
    {
        if ($template === '') {
            return '';
        }

        try {
            $starts = $event->starts_at ? Carbon::parse($event->starts_at)->locale('pt_BR') : null;
            $ends = $event->ends_at ? Carbon::parse($event->ends_at)->locale('pt_BR') : null;
        } catch (\Throwable) {
            $starts = null;
            $ends = null;
        }

        $location = $event->location?->name ?? '';
        $weekday = $starts ? mb_strtolower($starts->translatedFormat('l')) : '';
        $regional = BaptismListReportBuilder::regionalDisplayName($event->regional?->name);

        return strtr($template, [
            '{regional}' => $regional,
            '{date}' => $starts ? $starts->format('d/m/Y') : '',
            '{day_month}' => $starts ? $starts->format('d/m') : '',
            '{date_long}' => $starts ? $starts->translatedFormat('d \\d\\e F \\d\\e Y') : '',
            '{weekday}' => $weekday,
            '{time}' => $starts ? $starts->format('H:i') : '',
            '{end_time}' => $ends ? $ends->format('H:i') : '',
            '{location}' => $location,
            '{title}' => (string) ($event->title ?? ''),
        ]);
    }

    public function defaultIncludeOnBaptismList(?EventType $type): bool
    {
        return (bool) ($type?->meta['default_include_on_baptism_list'] ?? false);
    }
}
