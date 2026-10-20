<?php

use App\Models\Regional;
use App\Models\User;
use App\Services\PublicAudienceCatalogService;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$user = User::query()->where('email', 'admin@ccb.demo')->first()
    ?? User::query()->first();

if (! $user) {
    echo "Sem utilizador\n";
    exit(1);
}

echo "User: {$user->email} super=".($user->isSuperAdmin() ? 'yes' : 'no')." regional_id={$user->regional_id}\n";
echo 'accessibleRegionalIds: '.implode(',', $user->accessibleRegionalIds())."\n\n";

$catalog = app(PublicAudienceCatalogService::class);

foreach ([1, 2, 3] as $rid) {
    $reg = Regional::find($rid);
    if (! $reg) {
        continue;
    }

    $base = $catalog->departmentsQueryForUser($user);
    $withRegional = (clone $base)->where(fn ($q) => $q->forRegionalContext($rid));
    $catalogOnly = (clone $withRegional)->whereNotNull('public_subgroup_id');
    $regScope = (clone $withRegional)->where('scope', 'regional')->whereNotNull('public_subgroup_id');
    $admScope = (clone $withRegional)->where('scope', 'administration')->whereNotNull('public_subgroup_id');

    echo "{$reg->name} (id={$rid}):\n";
    echo "  total context: {$withRegional->count()}\n";
    echo "  catalog (com subgrupo): {$catalogOnly->count()}\n";
    echo "  catalog regional: {$regScope->count()}\n";
    echo "  catalog admin: {$admScope->count()}\n\n";
}
