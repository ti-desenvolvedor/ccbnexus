<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            CcbReferenceDataSeeder::class,
            EventTypeAudiencePresetSeeder::class,
            MinimalOrganizationSeeder::class,
            ItapecericaDaSerraPrayerHousesSeeder::class,
            PublicAudienceTaxonomySeeder::class,
            DevAdminUserSeeder::class,
            RegionalEmbuArtesAgenda2026Seeder::class,
            EmbuMinistryServantsSeeder::class,
        ]);
    }
}
