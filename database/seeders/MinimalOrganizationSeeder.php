<?php

namespace Database\Seeders;

use App\Models\Administration;
use App\Models\Location;
use App\Models\PrayerHouse;
use App\Models\Regional;
use Illuminate\Database\Seeder;

class MinimalOrganizationSeeder extends Seeder
{
    /**
     * Dados mínimos para desenvolvimento (regional, administração, casa, local).
     */
    public function run(): void
    {
        $location = Location::query()->updateOrCreate(
            ['name' => 'Sede demonstração'],
            [
                'line1' => 'Rua Exemplo',
                'number' => '100',
                'complement' => 'Sala 1',
                'district' => 'Centro',
                'city' => 'São Paulo',
                'state' => 'SP',
                'postal_code' => '01000-000',
                'country' => 'BR',
            ],
        );

        $regional = Regional::query()->updateOrCreate(
            ['slug' => 'ccb-demo'],
            [
                'name' => 'Regional demonstração',
                'location_id' => $location->id,
                'is_active' => true,
            ],
        );

        $administration = Administration::query()->updateOrCreate(
            ['regional_id' => $regional->id, 'slug' => 'adm-demo'],
            [
                'name' => 'Administração demonstração',
                'location_id' => $location->id,
                'is_active' => true,
            ],
        );

        PrayerHouse::query()->updateOrCreate(
            ['administration_id' => $administration->id, 'slug' => 'casa-demo'],
            [
                'name' => 'Casa de oração demonstração',
                'location_id' => $location->id,
                'is_active' => true,
            ],
        );
    }
}
