<?php

namespace App\Services\Agenda;

use App\Models\Holiday;
use App\Models\HolidaySyncLog;

class CalendarSync
{
    public function __construct(
        protected NationalHolidayFeed $nationals,
        protected BrazilianHolidayCatalog $catalog,
        protected CivicCalendarDates $civic,
    ) {}

    /**
     * @param  list<int>  $years
     */
    public function syncIfDue(array $years, bool $force = false): HolidaySyncLog
    {
        $period = now()->format('Y-m');
        $done = HolidaySyncLog::query()
            ->where('period', $period)
            ->where('status', 'success')
            ->latest('ran_at')
            ->first();

        if ($done && ! $force) {
            return $done;
        }

        $years = $this->years($years);
        $inserted = 0;
        $updated = 0;
        $sources = [];

        foreach ($years as $year) {
            foreach ($this->rowsForYear($year) as $row) {
                $holiday = Holiday::query()->updateOrCreate(
                    ['source_key' => $row['source_key']],
                    $row,
                );
                $sources[] = $this->nationals->lastSource();
                if ($holiday->wasRecentlyCreated) {
                    $inserted++;
                } else {
                    $updated++;
                }
            }
        }

        $source = $this->sourceLabel($sources);
        $yearLabel = implode(' e ', $years);
        $message = $inserted.' datas novas e '.$updated.' atualizadas de '.$yearLabel.'. Fonte dos nacionais: '.$source.'. Estaduais, Enem e eleições entram no mesmo calendário, sem duplicar.';

        return HolidaySyncLog::query()->create([
            'period' => $period,
            'status' => 'success',
            'source' => $source,
            'years' => $years,
            'inserted' => $inserted,
            'updated' => $updated,
            'message' => $message,
            'ran_at' => now(),
        ]);
    }

    /**
     * @param  list<int>  $years
     * @return list<int>
     */
    public function years(array $years): array
    {
        $years[] = (int) now()->year;
        $years[] = (int) now()->year + 1;
        $years = array_values(array_unique(array_map('intval', $years)));
        sort($years);

        return array_values(array_filter($years, fn (int $year): bool => $year >= 2000 && $year <= 2100));
    }

    /**
     * @return list<array{observed_on: string, name: string, scope: string, state: ?string, source_key: string}>
     */
    private function rowsForYear(int $year): array
    {
        return array_merge(
            $this->nationals->forYear($year),
            $this->catalog->stateHolidays($year),
            $this->civic->forYear($year),
        );
    }

    /**
     * @param  list<string>  $sources
     */
    private function sourceLabel(array $sources): string
    {
        $sources = array_values(array_unique($sources));
        if ($sources === []) {
            return 'catalog';
        }
        if (count($sources) === 1) {
            return $sources[0];
        }

        return 'brasilapi+catalog';
    }
}
