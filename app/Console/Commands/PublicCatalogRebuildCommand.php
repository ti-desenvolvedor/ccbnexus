<?php

namespace App\Console\Commands;

use App\Models\Regional;
use App\Services\PublicAudience\PublicCatalogOrganogramSeeder;
use Illuminate\Console\Command;

class PublicCatalogRebuildCommand extends Command
{
    protected $signature = 'public-catalog:rebuild
                            {--regional= : Slug da regional (todas as ativas se omitido)}
                            {--force : Confirma remoção e reseed sem prompt}';

    protected $description = 'Recria o organograma do catálogo público (purge + seed canónico) por regional';

    public function handle(PublicCatalogOrganogramSeeder $seeder): int
    {
        if (! $this->option('force') && ! $this->confirm('Isto apaga departamentos/cargos do catálogo público da(s) regional(is) e pivots em eventos. Continuar?')) {
            $this->warn('Cancelado.');

            return self::SUCCESS;
        }

        $slug = $this->option('regional');
        $query = Regional::query()->orderBy('name');

        if ($slug) {
            $query->where('slug', $slug);
        } else {
            $query->where(fn ($q) => $q->where('is_active', true)->orWhereNull('is_active'));
        }

        $regionals = $query->get();

        if ($regionals->isEmpty()) {
            $this->error('Nenhuma regional encontrada.');

            return self::FAILURE;
        }

        foreach ($regionals as $regional) {
            $this->info("A processar: {$regional->name} ({$regional->slug})");

            $purged = $seeder->purgeForRegional($regional);
            $this->line(sprintf(
                '  Removido: %d grupos, %d subgrupos, %d deptos, %d cargos, %d pivots evento',
                $purged['groups_deleted'],
                $purged['subgroups_deleted'],
                $purged['departments_deleted'],
                $purged['positions_deleted'],
                $purged['pivot_deleted'],
            ));

            $seeded = $seeder->seedForRegional($regional);
            $this->line(sprintf(
                '  Criado: %d grupos, %d instâncias de departamento, %d cargos',
                $seeded['groups'],
                $seeded['departments'],
                $seeded['positions'],
            ));
        }

        $this->info('Rebuild concluído.');

        return self::SUCCESS;
    }
}
