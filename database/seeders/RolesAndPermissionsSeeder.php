<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Eventos (baseline do prompt)
            'criar_evento',
            'editar_evento',
            'aprovar_evento',
            'cancelar_evento',
            'visualizar_evento',

            // Usuários / acesso
            'gerenciar_usuarios',
            'aprovar_acesso',

            // Avisos / relatórios
            'gerenciar_avisos',
            'visualizar_relatorios',

            // Organização / infraestrutura (domínio CCB Nexus)
            'gerenciar_regionais',
            'gerenciar_administracoes',
            'gerenciar_casas_oracao',
            'gerenciar_enderecos',
            'gerenciar_salas',
            'criar_reserva_sala',
            'cancelar_reserva_sala',
            'aprovar_reserva_sala',

            // Governança transversal (AGENTS.md)
            'atuar_como_facilitador',
            'moderar_conteudo',
        ];

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $all = Permission::query()->where('guard_name', 'web')->pluck('name')->all();

        $rolePermissions = [
            'Administrador' => $all,
            'Secretaria' => [
                'criar_evento',
                'editar_evento',
                'visualizar_evento',
                'aprovar_acesso',
                'gerenciar_avisos',
                'visualizar_relatorios',
                'gerenciar_administracoes',
                'gerenciar_casas_oracao',
                'gerenciar_enderecos',
                'gerenciar_salas',
                'criar_reserva_sala',
                'cancelar_reserva_sala',
                'aprovar_reserva_sala',
                'atuar_como_facilitador',
            ],
            'Aprovador' => [
                'visualizar_evento',
                'aprovar_evento',
                'visualizar_relatorios',
                'aprovar_reserva_sala',
                'moderar_conteudo',
            ],
            'Administração' => [
                'visualizar_evento',
                'criar_evento',
                'editar_evento',
                'visualizar_relatorios',
                'gerenciar_enderecos',
                'gerenciar_salas',
                'criar_reserva_sala',
                'cancelar_reserva_sala',
                'atuar_como_facilitador',
            ],
            'Visualizador' => [
                'visualizar_evento',
                'visualizar_relatorios',
            ],
            'Facilitador' => [
                'atuar_como_facilitador',
                'visualizar_evento',
                'visualizar_relatorios',
            ],
            'Moderador' => [
                'moderar_conteudo',
                'visualizar_evento',
                'visualizar_relatorios',
                'aprovar_reserva_sala',
                'gerenciar_avisos',
            ],
        ];

        foreach ($rolePermissions as $roleName => $perms) {
            $role = Role::query()->firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($perms);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
