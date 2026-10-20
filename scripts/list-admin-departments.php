<?php

use App\Models\Administration;
use App\Models\PublicDepartment;
use App\Models\Regional;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$catalog = require database_path('seeders/data/administrative_departments.php');
$expectedSlugs = [];
foreach ($catalog as $block) {
    foreach ($block['departments'] as $d) {
        $expectedSlugs[] = $d['slug'];
    }
}

$regionals = Regional::query()->where('is_active', true)->orderBy('name')->get();

echo "\n--- Todas as administrações ---\n";
Administration::query()->with('regional:id,name,slug')->orderBy('regional_id')->get()
    ->each(function ($a) {
        echo "  {$a->regional?->name} / {$a->name} ({$a->slug}) — ".($a->is_active ? 'ativa' : 'inativa')."\n";
    });

foreach ($regionals as $reg) {
    echo "\n=== {$reg->name} ({$reg->slug}) ===\n";

    foreach (['regional', 'administration'] as $scope) {
        $query = PublicDepartment::query()
            ->where('scope', $scope)
            ->whereNotNull('public_subgroup_id');

        if ($scope === 'regional') {
            $query->where('regional_id', $reg->id);
        } else {
            $query->whereHas('administration', fn ($q) => $q->where('regional_id', $reg->id));
        }

        $found = $query->pluck('slug')->all();
        $missing = array_diff($expectedSlugs, $found);
        $extra = array_diff($found, $expectedSlugs);

        echo "  {$scope}: ".count($found).'/17';
        if ($missing) {
            echo ' — FALTAM: '.implode(', ', $missing);
        }
        if ($extra) {
            echo ' — EXTRAS: '.implode(', ', $extra);
        }
        echo "\n";
    }

    $admins = Administration::query()->where('regional_id', $reg->id)->get(['id', 'name', 'slug', 'is_active']);
    echo '  Administrações: ';
    if ($admins->isEmpty()) {
        echo "nenhuma\n";
    } else {
        echo $admins->map(fn ($a) => "{$a->name} (".($a->is_active ? 'ativa' : 'inativa').')')->implode(', ')."\n";
    }
}
