<?php

namespace App\Services\Agenda;

use App\Models\Holiday;
use App\Models\Regional;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class AgendaHolidayMarkers
{
    public function __construct(
        protected RegionalHolidayPlace $place,
    ) {}

    /**
     * @return array<string, list<string>>
     */
    public function forRange(?CarbonInterface $start, ?CarbonInterface $end, ?int $regionalId): array
    {
        if ($start === null || $end === null) {
            return [];
        }

        $state = null;
        if ($regionalId !== null) {
            $regional = Regional::query()->find($regionalId);
            $state = $regional ? $this->place->resolve($regional)['state'] : null;
        }

        $query = Holiday::query()
            ->whereDate('observed_on', '>=', $start->toDateString())
            ->whereDate('observed_on', '<', $end->toDateString())
            ->where(function ($scope) use ($state) {
                $scope->whereIn('scope', ['national', 'event']);
                if ($state !== null) {
                    $scope->orWhere(function ($stateScope) use ($state) {
                        $stateScope->where('scope', 'state')->where('state', $state);
                    });
                }
            })
            ->orderBy('observed_on')
            ->orderBy('id')
            ->get(['observed_on', 'name']);

        $byDate = [];
        foreach ($query as $holiday) {
            $byDate[$holiday->observed_on->toDateString()][] = $holiday->name;
        }

        return $byDate;
    }

    /**
     * @return array<string, true>
     */
    public function datesForYear(int $year, ?int $regionalId): array
    {
        $start = Carbon::create($year, 1, 1, 0, 0, 0, config('app.timezone'))->startOfDay();
        $end = $start->copy()->addYear();
        $dates = [];
        foreach (array_keys($this->forRange($start, $end, $regionalId)) as $date) {
            $dates[$date] = true;
        }

        return $dates;
    }
}
