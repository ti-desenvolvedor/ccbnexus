<?php

namespace Database\Seeders;

use App\Models\Regional;
use App\Models\User;
use App\Support\PasswordStrength;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DevAdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = 'thomasgoncalves@yahoo.com.br';

        $password = (string) config('nexus.initial_admin_password', '');
        if ($password === '') {
            $this->command?->warn('INITIAL_ADMIN_PASSWORD vazio — admin nao criado/atualizado. Defina no .env.');

            return;
        }

        $regional = Regional::query()->where('slug', 'embu-das-artes')->first()
            ?? Regional::query()->where('slug', 'ccb-demo')->first();

        $user = User::query()->where('email', $email)->first();
        $resetPassword = (bool) config('nexus.reset_admin_password', false);

        if ($user === null) {
            $user = User::query()->create([
                'email' => $email,
                'name' => 'Thomas Gonçalves',
                ...PasswordStrength::stored($password),
                'email_verified_at' => now(),
                'regional_id' => $regional?->id,
            ]);
            $user->forceFill(['is_super_admin' => true])->save();
        } else {
            $payload = [
                'name' => 'Thomas Gonçalves',
                'email_verified_at' => $user->email_verified_at ?? now(),
                'regional_id' => $regional?->id,
            ];

            if ($resetPassword && ! Hash::check($password, (string) $user->password)) {
                $payload = array_merge($payload, PasswordStrength::stored($password));
                $this->command?->warn('RESET_ADMIN_PASSWORD=true: senha do admin reaplicada a partir de INITIAL_ADMIN_PASSWORD.');
            } elseif (Hash::check($password, (string) $user->password)) {
                $payload['password_strength'] = PasswordStrength::level($password);
            }

            // Query builder: não dispara activity log / eventos que invalidam sessão à toa.
            User::query()->whereKey($user->id)->update(array_merge($payload, [
                'is_super_admin' => true,
                'updated_at' => now(),
            ]));
            $user->refresh();
        }

        $role = Role::query()->where('name', 'Administrador')->where('guard_name', 'web')->first();
        if ($role) {
            $user->syncRoles([$role->name]);
        }
    }
}
