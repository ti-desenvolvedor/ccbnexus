<?php

namespace Tests\Feature;

use App\Models\PublicDepartment;
use App\Models\PublicPosition;
use App\Models\Regional;
use App\Models\User;
use App\Services\PublicAudience\PublicAudienceCatalogDeleteService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PublicCatalogDeletePermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_manage_only_cannot_delete_catalog_items(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->givePermissionTo('gerenciar_avisos');

        $regional = Regional::query()->create([
            'name' => 'R',
            'slug' => 'r-del',
            'is_active' => true,
        ]);

        $department = PublicDepartment::query()->create([
            'scope' => PublicDepartment::SCOPE_REGIONAL,
            'regional_id' => $regional->id,
            'name' => 'Teste',
            'slug' => 'teste-del',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->assertTrue($user->can('update', $department));
        $this->assertFalse($user->can('delete', $department));
    }

    public function test_user_with_delete_permission_can_remove_position(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->givePermissionTo('excluir_catalogo_publico');

        $regional = Regional::query()->create([
            'name' => 'R2',
            'slug' => 'r2-del',
            'is_active' => true,
        ]);

        $department = PublicDepartment::query()->create([
            'scope' => PublicDepartment::SCOPE_REGIONAL,
            'regional_id' => $regional->id,
            'name' => 'Dept',
            'slug' => 'dept-del',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $position = PublicPosition::query()->create([
            'public_department_id' => $department->id,
            'name' => 'Cargo teste',
            'slug' => 'cargo-teste',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->assertTrue($user->can('delete', $position));

        $deleter = new PublicAudienceCatalogDeleteService;
        $deleter->deletePosition($position);

        $this->assertDatabaseMissing('public_positions', ['id' => $position->id]);
    }

    public function test_department_with_positions_can_be_deleted_with_cascade(): void
    {
        $regional = Regional::query()->create([
            'name' => 'R4',
            'slug' => 'r4-del',
            'is_active' => true,
        ]);

        $department = PublicDepartment::query()->create([
            'scope' => PublicDepartment::SCOPE_REGIONAL,
            'regional_id' => $regional->id,
            'name' => 'Dept cascade',
            'slug' => 'dept-cascade',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $position = PublicPosition::query()->create([
            'public_department_id' => $department->id,
            'name' => 'Cargo',
            'slug' => 'cargo-cascade',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        (new PublicAudienceCatalogDeleteService)->deleteDepartment($department, true);

        $this->assertDatabaseMissing('public_departments', ['id' => $department->id]);
        $this->assertDatabaseMissing('public_positions', ['id' => $position->id]);
    }

    public function test_department_with_positions_cannot_be_deleted_without_cascade(): void
    {
        $regional = Regional::query()->create([
            'name' => 'R3',
            'slug' => 'r3-del',
            'is_active' => true,
        ]);

        $department = PublicDepartment::query()->create([
            'scope' => PublicDepartment::SCOPE_REGIONAL,
            'regional_id' => $regional->id,
            'name' => 'Dept bloqueado',
            'slug' => 'dept-bloq',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        PublicPosition::query()->create([
            'public_department_id' => $department->id,
            'name' => 'Cargo',
            'slug' => 'cargo-bloq',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->expectException(ValidationException::class);

        (new PublicAudienceCatalogDeleteService)->deleteDepartment($department);
    }
}
