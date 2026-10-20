<?php

use App\Models\Administration;
use App\Models\PublicDepartment;
use App\Models\Regional;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

foreach (Regional::query()->orderBy('name')->get() as $r) {
    $admins = Administration::query()->where('regional_id', $r->id)->get();
    $deptReg = PublicDepartment::where('scope', 'regional')->where('regional_id', $r->id)->whereNotNull('public_subgroup_id')->count();
    $deptAdm = PublicDepartment::where('scope', 'administration')
        ->whereIn('administration_id', $admins->pluck('id'))
        ->whereNotNull('public_subgroup_id')->count();
    echo "R#{$r->id} {$r->name} ({$r->slug}) | admins={$admins->count()} | dept R={$deptReg} A={$deptAdm}\n";
    foreach ($admins as $a) {
        $c = PublicDepartment::where('scope', 'administration')->where('administration_id', $a->id)->whereNotNull('public_subgroup_id')->count();
        echo "    - {$a->name} ({$a->slug}) ".($a->is_active ? 'ativa' : 'inativa').": {$c}/17\n";
    }
}
