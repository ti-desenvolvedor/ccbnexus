<?php

namespace Database\Seeders;

use App\Models\Regional;
use App\Services\PublicAudience\PublicCatalogOrganogramSeeder;
use Illuminate\Database\Seeder;

class PublicAudienceTaxonomySeeder extends Seeder
{
    public function run(): void
    {
        Regional::query()->where('slug', 'ccb-demo')->update(['is_active' => true]);

        $seeder = new PublicCatalogOrganogramSeeder;

        $regionals = Regional::query()
            ->where(function ($q) {
                $q->where('is_active', true)->orWhereNull('is_active');
            })
            ->orderBy('name')
            ->get();

        if ($regionals->isEmpty()) {
            $this->command?->warn('PublicAudienceTaxonomySeeder: nenhuma regional ativa.');

            return;
        }

        foreach ($regionals as $regional) {
            $seeder->purgeForRegional($regional);
            $result = $seeder->seedForRegional($regional);

            $this->command?->info(sprintf(
                '%s: organograma recriado — %d grupos, %d deptos (instâncias), %d cargos.',
                $regional->name,
                $result['groups'],
                $result['departments'],
                $result['positions'],
            ));
        }
    }
}
