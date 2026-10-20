<?php

namespace Tests\Feature;

use App\Models\PublicGroup;
use App\Models\PublicSubgroup;
use App\Models\Regional;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PublicCatalogUiTest extends TestCase
{
    use RefreshDatabase;

    protected function catalogManager(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findByName('gerenciar_avisos'));

        return $user;
    }

    public function test_catalog_hub_shows_four_axes(): void
    {
        $user = $this->catalogManager();

        $this->actingAs($user)
            ->get(route('agenda.public-catalog.index'))
            ->assertOk()
            ->assertSee(__('Estrutura administrativa (Grupos I–V)'))
            ->assertSee(__('Serviços operacionais unificados'))
            ->assertSee(__('Ministério'))
            ->assertSee(__('Música'));
    }

    public function test_axis_explorer_routes_return_ok(): void
    {
        $user = $this->catalogManager();

        $this->actingAs($user)
            ->get(route('agenda.public-catalog.administrative'))
            ->assertOk()
            ->assertSee(__('Estrutura administrativa'));

        $this->actingAs($user)
            ->get(route('agenda.public-catalog.operational-services'))
            ->assertOk()
            ->assertSee(__('Serviços operacionais unificados'), false);

        $this->actingAs($user)
            ->get(route('agenda.public-catalog.ministry'))
            ->assertOk()
            ->assertSee(__('Ministério'), false);

        $this->actingAs($user)
            ->get(route('agenda.public-catalog.music'))
            ->assertOk()
            ->assertSee(__('Música'), false);
    }

    public function test_subgroup_departments_page_returns_ok(): void
    {
        $user = $this->catalogManager();

        $regional = Regional::query()->create([
            'name' => 'R1',
            'slug' => 'r1',
            'is_active' => true,
        ]);

        $user->update(['regional_id' => $regional->id]);

        $group = PublicGroup::query()->create([
            'regional_id' => $regional->id,
            'slug' => 'colaboradores-administrativos',
            'name' => 'Colaboradores administrativos',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $subgroup = PublicSubgroup::query()->create([
            'public_group_id' => $group->id,
            'slug' => 'grupo-i',
            'name' => 'Grupo I',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $this->actingAs($user)
            ->get(route('agenda.public-subgroup.departments', ['group' => $group, 'subgroup' => $subgroup]))
            ->assertOk()
            ->assertSee('Grupo I');
    }
}
