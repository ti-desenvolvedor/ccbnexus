<?php

use App\Models\Administration;
use App\Models\PublicDepartment;
use App\Models\PublicSubgroup;
use App\Models\Regional;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$regionals = Regional::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'slug']);

if ($regionals->isEmpty()) {
    echo "Nenhuma regional ativa.\n";
    exit(0);
}

foreach ($regionals as $reg) {
    $subs = PublicSubgroup::query()
        ->whereHas('group', fn ($q) => $q->where('regional_id', $reg->id)->where('slug', 'colaboradores-administrativos'))
        ->where('slug', 'like', 'grupo-%')
        ->count();

    $deptReg = PublicDepartment::query()
        ->where('scope', 'regional')
        ->where('regional_id', $reg->id)
        ->whereNotNull('public_subgroup_id')
        ->count();

    $deptAdm = PublicDepartment::query()
        ->where('scope', 'administration')
        ->whereHas('administration', fn ($q) => $q->where('regional_id', $reg->id))
        ->whereNotNull('public_subgroup_id')
        ->count();

    $admins = Administration::query()
        ->where('regional_id', $reg->id)
        ->where('is_active', true)
        ->count();

    echo "{$reg->name} ({$reg->slug}): subgrupos={$subs}, dept_regional={$deptReg}, dept_admin={$deptAdm}, admins_ativas={$admins}\n";
}
