<?php

namespace App\Services\Agenda;

/**
 * Critério único de leitura da agenda (CAL-01). Sem janela em mode=series.
 */
final class AgendaReadCriteria
{
    public const MODE_OCCURRENCES = 'occurrences';

    public const MODE_SERIES = 'series';

    public const WINDOW_WEEK = 'week';

    public const WINDOW_MONTH = 'month';

    public const CHANNELS = ['bulletin', 'whatsapp', 'bulletin_only', 'whatsapp_only'];

    public function __construct(
        public readonly string $mode,
        public readonly ?string $window = null,
        public readonly ?string $cursor = null,
        public readonly string $search = '',
        public readonly ?int $eventTypeId = null,
        public readonly string $channel = '',
        public readonly string $status = '',
        public readonly bool $forPdf = false,
        public readonly int $page = 1,
        public readonly ?int $perPage = null,
    ) {}

    public static function pdf(
        string $cursor,
        string $search = '',
        ?int $eventTypeId = null,
        string $channel = '',
        string $status = '',
    ): self {
        return new self(
            mode: self::MODE_OCCURRENCES,
            window: self::WINDOW_MONTH,
            cursor: $cursor,
            search: $search,
            eventTypeId: $eventTypeId,
            channel: $channel,
            status: $status,
            forPdf: true,
        );
    }
}
