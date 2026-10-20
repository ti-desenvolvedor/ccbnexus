<?php

namespace Database\Seeders;

use App\Models\Administration;
use App\Models\Location;
use App\Models\PrayerHouse;
use App\Models\Regional;
use Illuminate\Database\Seeder;

class ItapecericaDaSerraPrayerHousesSeeder extends Seeder
{
    /**
     * Casas de oração do município de Itapecerica da Serra (SP),
     * vinculadas à Regional Embu das Artes.
     */
    public function run(): void
    {
        $regionalLocation = Location::query()->updateOrCreate(
            ['name' => 'Embu das Artes — referência regional'],
            [
                'line1' => null,
                'city' => 'Embu das Artes',
                'state' => 'SP',
                'country' => 'BR',
            ],
        );

        $municipalityLocation = Location::query()->updateOrCreate(
            ['name' => 'Itapecerica da Serra — referência municipal'],
            [
                'line1' => null,
                'city' => 'Itapecerica da Serra',
                'state' => 'SP',
                'country' => 'BR',
            ],
        );

        // Mesmo slug do Agenda 2026 — evita duplicar "Regional Embu das Artes" no seletor.
        $regional = Regional::query()->updateOrCreate(
            ['slug' => 'embu-das-artes'],
            [
                'name' => 'Regional Embu das Artes',
                'location_id' => $regionalLocation->id,
                'is_active' => true,
            ],
        );

        $regionalLocation->update(['regional_id' => $regional->id, 'administration_id' => null]);

        $administration = Administration::query()->updateOrCreate(
            ['regional_id' => $regional->id, 'slug' => 'adm-itapecerica-da-serra'],
            [
                'name' => 'Administração Itapecerica da Serra',
                'location_id' => $municipalityLocation->id,
                'is_active' => true,
            ],
        );

        $municipalityLocation->update(['administration_id' => $administration->id, 'regional_id' => null]);

        $houses = [
            ['official_code' => 'BR 22-1637', 'name' => 'Jardim Marilu', 'slug' => 'jardim-marilu'],
            ['official_code' => 'BR 22-4087', 'name' => 'Aldeinha', 'slug' => 'aldeinha'],
            ['official_code' => 'BR 22-1642', 'name' => 'Conselho', 'slug' => 'conselho'],
            ['official_code' => 'BR 22-2068', 'name' => 'Crispim', 'slug' => 'crispim'],
            ['official_code' => 'BR 22-3001', 'name' => 'Itaquaciara', 'slug' => 'itaquaciara'],
            ['official_code' => 'BR 22-2154', 'name' => 'Jardim Branca Flor', 'slug' => 'jardim-branca-flor'],
            ['official_code' => 'BR 22-3954', 'name' => 'Jardim Cinira', 'slug' => 'jardim-cinira'],
            ['official_code' => 'BR 22-4118', 'name' => 'Jardim das Oliveiras', 'slug' => 'jardim-das-oliveiras'],
            ['official_code' => 'BR 22-4048', 'name' => 'Jardim Horacina', 'slug' => 'jardim-horacina'],
            ['official_code' => 'BR 22-2295', 'name' => 'Jardim Jacira', 'slug' => 'jardim-jacira'],
            ['official_code' => 'BR 22-2148', 'name' => 'Jardim São Pedro', 'slug' => 'jardim-sao-pedro'],
            ['official_code' => 'BR 22-2644', 'name' => 'Parque Horizonte Azul', 'slug' => 'parque-horizonte-azul'],
            ['official_code' => 'BR 22-2010', 'name' => 'Parque Paraíso', 'slug' => 'parque-paraiso'],
            ['official_code' => 'BR 21-1534', 'name' => 'Parque Santa Amélia', 'slug' => 'parque-santa-amelia'],
            ['official_code' => 'BR 22-2996', 'name' => 'Piqueri', 'slug' => 'piqueri'],
            ['official_code' => 'BR 22-1639', 'name' => 'Potuverá', 'slug' => 'potuvera'],
            ['official_code' => 'BR 21-1004', 'name' => 'Serra Linda', 'slug' => 'serra-linda'],
            ['official_code' => 'BR 22-3964', 'name' => 'Vila Geni', 'slug' => 'vila-geni'],
            ['official_code' => 'BR 22-3523', 'name' => 'Vila Guiomar', 'slug' => 'vila-guiomar'],
            ['official_code' => 'BR 22-1640', 'name' => 'Vila Santa Maria', 'slug' => 'vila-santa-maria'],
        ];

        foreach ($houses as $row) {
            PrayerHouse::query()->updateOrCreate(
                ['official_code' => $row['official_code']],
                [
                    'administration_id' => $administration->id,
                    'name' => $row['name'],
                    'slug' => $row['slug'],
                    'is_active' => true,
                    'meta' => [
                        'municipality' => 'Itapecerica da Serra',
                        'state' => 'SP',
                    ],
                ],
            );
        }

        $this->command?->info(sprintf(
            'Itapecerica da Serra: %d casas de oração em "%s" (%s).',
            count($houses),
            $administration->name,
            $regional->name,
        ));
    }
}
