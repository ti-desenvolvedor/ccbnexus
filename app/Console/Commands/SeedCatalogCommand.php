<?php

namespace App\Console\Commands;

use Database\Seeders\CcbReferenceDataSeeder;
use Database\Seeders\EmbuMinistryServantsSeeder;
use Database\Seeders\EventTypeAudiencePresetSeeder;
use Database\Seeders\RegionalBaptismListNoticesSeeder;
use Database\Seeders\RegionalEmbuArtesAgenda2026Seeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Console\Command;

class SeedCatalogCommand extends Command
{
    protected $signature = 'nexus:seed-catalog';

    protected $description = 'Popula o catálogo de produção (papéis, tipos, agenda, avisos e servos). Não cria admin nem regional demo.';

    public function handle(): int
    {
        $seeders = [
            RolesAndPermissionsSeeder::class,
            CcbReferenceDataSeeder::class,
            EventTypeAudiencePresetSeeder::class,
            RegionalEmbuArtesAgenda2026Seeder::class,
            RegionalBaptismListNoticesSeeder::class,
            EmbuMinistryServantsSeeder::class,
        ];

        foreach ($seeders as $seeder) {
            $code = $this->call('db:seed', ['--class' => $seeder, '--force' => true]);
            if ($code !== self::SUCCESS) {
                return $code;
            }
        }

        $this->info('Catálogo de produção atualizado.');

        return self::SUCCESS;
    }
}
