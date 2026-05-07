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

        $password = (string) env('INITIAL_ADMIN_PASSWORD', 'ChangeMe!123');

        $regional = Regional::query()->where('slug', 'ccb-demo')->first();

        $user = User::query()->where('email', $email)->first();
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
            // Não alterar a password em re-seeds: evita “credenciais inválidas” após mudança manual.
            $user->update([
                'name' => 'Thomas Gonçalves',
                'email_verified_at' => $user->email_verified_at ?? now(),
                'is_super_admin' => true,
                'regional_id' => $regional?->id,
            ]);
        }

        $role = Role::query()->where('name', 'Administrador')->where('guard_name', 'web')->first();
        if ($role) {
            $user->syncRoles([$role->name]);
        }
    }
}
