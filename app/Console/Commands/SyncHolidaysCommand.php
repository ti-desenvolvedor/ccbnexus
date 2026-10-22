<?php

namespace App\Console\Commands;

use App\Services\Agenda\CalendarSync;
use App\Services\Agenda\RecurrenceHorizon;
use Illuminate\Console\Command;

class SyncHolidaysCommand extends Command
{
    protected $signature = 'nexus:sync-holidays {--force : Roda de novo mesmo que este mês já tenha sincronizado}';

    protected $description = 'Sincroniza feriados do ano atual e do seguinte, e prolonga recorrências anuais. Uma vez por mês, sem duplicar.';

    public function handle(CalendarSync $sync, RecurrenceHorizon $horizon): int
    {
        $log = $sync->syncIfDue([(int) now()->year], (bool) $this->option('force'));
        $roll = $horizon->extendOpenSeries();
        $this->info($log->message ?? 'Sincronização registrada.');
        $this->info('Recorrências até '.$roll['through'].': '.$roll['series'].' séries, '.$roll['dates'].' datas novas.');

        return self::SUCCESS;
    }
}
