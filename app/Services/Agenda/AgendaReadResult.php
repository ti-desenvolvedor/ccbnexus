<?php

namespace App\Services\Agenda;

use App\Models\Event;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class AgendaReadResult
{
    /**
     * @param  Collection<int, Event>|LengthAwarePaginator  $items
     */
    public function __construct(
        public readonly Collection|LengthAwarePaginator $items,
        public readonly string $mode,
        public readonly ?string $window,
        public readonly ?CarbonInterface $rangeStart,
        public readonly ?CarbonInterface $rangeEnd,
        public readonly ?int $regionalId,
        public readonly bool $includesDrafts,
        public readonly bool $truncated,
    ) {}

    /**
     * @return Collection<int, Event>
     */
    public function entries(): Collection
    {
        if ($this->items instanceof LengthAwarePaginator) {
            return $this->items->getCollection();
        }

        return $this->items;
    }
}
