<?php

namespace App\Services;

use App\Models\Event;
use App\Services\Recurrence\MonthlyNthRecurrencePlanner;
use App\Services\Recurrence\YearlyRecurrencePlanner;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EventRecurrenceService
{
    public const MAX_YEARLY_CHILD_OCCURRENCES = 100;

    public function __construct(
        protected MonthlyNthRecurrencePlanner $monthlyNth,
        protected YearlyRecurrencePlanner $yearly,
    ) {}

    /**
     * Gera ocorrências filhas (semanal ou mensal «énésimo dia da semana») até recurrence_until ou limite.
     */
    public function syncWeeklyOccurrences(Event $event): void
    {
        if ($event->is_occurrence || $event->parent_event_id) {
            return;
        }

        $hasWeekly = $event->recurrence_frequency === 'weekly' && $event->recurrence_until;
        $hasMonthlyNth = $event->recurrence_frequency === 'monthly_nth'
            && $event->recurrence_until
            && is_array($event->recurrence_config)
            && $this->monthlyNthConfigIsComplete($event->recurrence_config);

        $hasYearly = $event->recurrence_frequency === 'yearly'
            && $event->recurrence_until
            && is_array($event->recurrence_config)
            && $this->yearlyConfigIsComplete($event->recurrence_config);

        if (! $hasWeekly && ! $hasMonthlyNth && ! $hasYearly) {
            Event::query()
                ->where('parent_event_id', $event->id)
                ->where('is_occurrence', true)
                ->delete();

            return;
        }

        if ($hasWeekly) {
            $this->syncWeeklySeries($event);

            return;
        }

        if ($hasYearly) {
            $this->syncYearlySeries($event);

            return;
        }

        $this->syncMonthlyNthSeries($event);
    }

    public function countPlannedWeeklyOccurrences(
        \DateTimeInterface|string $startsAt,
        \DateTimeInterface|string $untilDate,
        int $intervalWeeks = 1,
    ): int {
        $intervalWeeks = max(1, min(12, $intervalWeeks));
        $start = Carbon::parse($startsAt);
        $until = Carbon::parse($untilDate)->endOfDay();
        $cursor = $start->copy()->addWeeks($intervalWeeks);
        $max = 52;
        $n = 0;

        while ($cursor->lte($until) && $n < $max) {
            $n++;
            $cursor->addWeeks($intervalWeeks);
        }

        return $n;
    }

    /**
     * @param  array{nth:int,weekday:int,months:array{type:string,list?:array<int>}}  $config
     */
    public function countPlannedMonthlyNthOccurrences(
        \DateTimeInterface|string $parentStartsAt,
        \DateTimeInterface|string $untilDate,
        array $config,
    ): int {
        if (! $this->monthlyNthConfigIsComplete($config)) {
            return 0;
        }

        return $this->monthlyNth->countOccurrences(
            Carbon::parse($parentStartsAt),
            Carbon::parse($untilDate)->endOfDay(),
            $config,
            52,
        );
    }

    /**
     * @param  array{interval_years?:int}  $config
     */
    public function countPlannedYearlyOccurrences(
        \DateTimeInterface|string $parentStartsAt,
        \DateTimeInterface|string $untilDate,
        array $config,
    ): int {
        if (! $this->yearlyConfigIsComplete($config)) {
            return 0;
        }

        return $this->yearly->countOccurrences(
            Carbon::parse($parentStartsAt),
            Carbon::parse($untilDate)->endOfDay(),
            $config,
            self::MAX_YEARLY_CHILD_OCCURRENCES,
        );
    }

    protected function yearlyConfigIsComplete(array $config): bool
    {
        $y = (int) ($config['interval_years'] ?? 0);

        return $y >= 1 && $y <= 50;
    }

    protected function monthlyNthConfigIsComplete(array $config): bool
    {
        if (! isset($config['nth'], $config['weekday'], $config['months'])) {
            return false;
        }

        $months = $config['months'];
        if (! is_array($months)) {
            return false;
        }

        if (isset($months['type'])) {
            if ($months['type'] === MonthlyNthRecurrencePlanner::MONTH_FILTER_LIST) {
                return isset($months['list']) && is_array($months['list']) && $months['list'] !== [];
            }

            return in_array($months['type'], [
                MonthlyNthRecurrencePlanner::MONTH_FILTER_ALL,
                MonthlyNthRecurrencePlanner::MONTH_FILTER_EVEN,
                MonthlyNthRecurrencePlanner::MONTH_FILTER_ODD,
            ], true);
        }

        if (isset($months['modes']) && is_array($months['modes'])) {
            $modes = array_values(array_unique(array_map(static fn ($m): string => (string) $m, $months['modes'])));
            if ($modes === []) {
                return false;
            }
            if (in_array(MonthlyNthRecurrencePlanner::MONTH_FILTER_ALL, $modes, true)) {
                return true;
            }
            if (in_array(MonthlyNthRecurrencePlanner::MONTH_FILTER_LIST, $modes, true)) {
                return isset($months['list']) && is_array($months['list']) && $months['list'] !== [];
            }

            return true;
        }

        return false;
    }

    protected function syncWeeklySeries(Event $event): void
    {
        DB::transaction(function () use ($event) {
            Event::query()
                ->where('parent_event_id', $event->id)
                ->where('is_occurrence', true)
                ->delete();

            $event->loadMissing('publicPositions');
            $positionIds = $event->publicPositions->pluck('id')->all();

            $start = Carbon::parse($event->starts_at);
            $duration = $start->diffInSeconds(Carbon::parse($event->ends_at));
            $until = Carbon::parse($event->recurrence_until)->endOfDay();
            $intervalWeeks = max(1, min(12, (int) ($event->recurrence_interval_weeks ?? 1)));
            $cursor = $start->copy()->addWeeks($intervalWeeks);
            $max = 52;
            $n = 0;

            while ($cursor->lte($until) && $n < $max) {
                $this->createOccurrence($event, $cursor->copy(), $duration, $positionIds);
                $cursor->addWeeks($intervalWeeks);
                $n++;
            }
        });
    }

    protected function syncMonthlyNthSeries(Event $event): void
    {
        $config = $event->recurrence_config ?? [];
        if (! $this->monthlyNthConfigIsComplete($config)) {
            Event::query()
                ->where('parent_event_id', $event->id)
                ->where('is_occurrence', true)
                ->delete();

            return;
        }

        DB::transaction(function () use ($event, $config) {
            Event::query()
                ->where('parent_event_id', $event->id)
                ->where('is_occurrence', true)
                ->delete();

            $event->loadMissing('publicPositions');
            $positionIds = $event->publicPositions->pluck('id')->all();

            $starts = $this->monthlyNth->occurrenceStarts(
                $event->starts_at,
                $event->recurrence_until,
                $config,
                52,
            );

            $duration = Carbon::parse($event->starts_at)->diffInSeconds(Carbon::parse($event->ends_at));

            foreach ($starts as $occStart) {
                $this->createOccurrence($event, $occStart->copy(), $duration, $positionIds);
            }
        });
    }

    protected function syncYearlySeries(Event $event): void
    {
        $config = $event->recurrence_config ?? [];
        if (! $this->yearlyConfigIsComplete($config)) {
            Event::query()
                ->where('parent_event_id', $event->id)
                ->where('is_occurrence', true)
                ->delete();

            return;
        }

        DB::transaction(function () use ($event, $config) {
            Event::query()
                ->where('parent_event_id', $event->id)
                ->where('is_occurrence', true)
                ->delete();

            $event->loadMissing('publicPositions');
            $positionIds = $event->publicPositions->pluck('id')->all();

            $starts = $this->yearly->occurrenceStarts(
                $event->starts_at,
                $event->recurrence_until,
                $config,
                self::MAX_YEARLY_CHILD_OCCURRENCES,
            );

            $duration = Carbon::parse($event->starts_at)->diffInSeconds(Carbon::parse($event->ends_at));

            foreach ($starts as $occStart) {
                $this->createOccurrence($event, $occStart->copy(), $duration, $positionIds);
            }
        });
    }

    /**
     * @param  array<int>  $positionIds
     */
    protected function createOccurrence(Event $event, Carbon $occStart, int $durationSeconds, array $positionIds): void
    {
        $occEnd = $occStart->copy()->addSeconds($durationSeconds);
        $base = $event->only([
            'regional_id',
            'title',
            'description',
            'event_type_id',
            'location_id',
            'meeting_room_id',
            'status',
            'created_by',
            'attendance_mode',
            'expected_attendees',
            'needs_sound_controller',
            'needs_av',
            'needs_parking',
            'needs_meals',
            'meal_coffee',
            'meal_lunch',
            'meal_snack',
            'meal_dinner',
            'needs_nursing',
            'needs_valet',
            'needs_other_materials',
            'other_materials_note',
        ]);

        $occ = Event::query()->create(array_merge($base, [
            'starts_at' => $occStart,
            'ends_at' => $occEnd,
            'parent_event_id' => $event->id,
            'is_occurrence' => true,
            'recurrence_frequency' => null,
            'recurrence_interval_weeks' => 1,
            'recurrence_until' => null,
            'recurrence_config' => null,
        ]));
        if ($positionIds !== []) {
            $occ->publicPositions()->sync($positionIds);
        }
    }
}
