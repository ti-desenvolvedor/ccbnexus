<?php

namespace App\Console\Commands;

use App\Services\Agenda\CalendarSync;
use Illuminate\Console\Command;

class SyncHolidaysCommand extends Command
{
    protected $signature = 'nexus:sync-holidays {--force : Roda de novo mesmo que este mês já tenha sincronizado}';

    protected $description = 'Sincroniza o ano inteiro de feriados, Enem e eleições. Uma vez por mês, sem duplicar.';

    public function handle(CalendarSync $sync): int
    {
        $log = $sync->syncIfDue([(int) now()->year], (bool) $this->option('force'));
        $this->info($log->message ?? 'Sincronização registrada.');

        return self::SUCCESS;
    }
}
