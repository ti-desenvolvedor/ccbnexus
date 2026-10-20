<?php

use App\Models\Regional;
use App\Models\User;
use App\Support\PasswordStrength;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$email = 'thomasgoncalves@yahoo.com.br';
$password = (string) config('nexus.initial_admin_password');
if ($password === '') {
    fwrite(STDERR, "INITIAL_ADMIN_PASSWORD vazio no .env\n");
    exit(1);
}

$regionalId = Regional::query()->where('slug', 'embu-das-artes')->value('id')
    ?? Regional::query()->where('is_active', true)->value('id');

$user = User::query()->where('email', $email)->first();
if (! $user) {
    $user = User::query()->create([
        'name' => 'Thomas Gonçalves',
        'email' => $email,
        ...PasswordStrength::stored($password),
        'email_verified_at' => now(),
        'regional_id' => $regionalId,
    ]);
} else {
    $user->forceFill([
        ...PasswordStrength::stored($password),
        'email_verified_at' => now(),
        'regional_id' => $regionalId ?? $user->regional_id,
    ])->save();
}

$user->forceFill(['is_super_admin' => true])->save();

$role = Role::query()->where('name', 'Administrador')->where('guard_name', 'web')->first();
if ($role) {
    $user->syncRoles([$role->name]);
}

$ok = Hash::check($password, $user->fresh()->password);
echo ($ok ? 'OK' : 'FAIL')." email={$email} regional_id=".($user->regional_id ?? 'null').PHP_EOL;
