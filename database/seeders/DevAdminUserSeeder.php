<?php

namespace Database\Seeders;

use App\Models\Regional;
use App\Models\User;
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

        $regional = Regional::query()->where('slug', 'ccb-demo')->first();

        $user = User::query()->where('email', $email)->first();
        $resetPassword = (bool) config('nexus.reset_admin_password', false);

        if ($user === null) {
            $user = User::query()->create([
                'email' => $email,
                'name' => 'Thomas Gonçalves',
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'is_super_admin' => true,
                'regional_id' => $regional?->id,
            ]);
        } else {
            $payload = [
                'name' => 'Thomas Gonçalves',
                'email_verified_at' => $user->email_verified_at ?? now(),
                'is_super_admin' => true,
                'regional_id' => $regional?->id,
            ];

            // Só regrava password quando pedido explicitamente (evita sobrescrever mudança manual).
            if ($resetPassword) {
                $payload['password'] = Hash::make($password);
            }

            $user->update($payload);
        }

        $role = Role::query()->where('name', 'Administrador')->where('guard_name', 'web')->first();
        if ($role) {
            $user->syncRoles([$role->name]);
        }
    }
}
