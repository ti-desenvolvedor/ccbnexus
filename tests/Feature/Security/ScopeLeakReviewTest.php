<?php

namespace Tests\Feature\Security;

use App\Livewire\Access\AccessRequestList;
use App\Livewire\Secretaria\MarkingsReportPage;
use App\Livewire\Secretaria\MinistryServantsPage;
use App\Livewire\Secretaria\RegionalNoticeManager;
use App\Models\AccessRequest;
use App\Models\Event;
use App\Models\Regional;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ScopeLeakReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_report_viewer_cannot_write_secretaria(): void
    {
        $regional = $this->regional('A');
        $viewer = $this->user($regional, ['visualizar_relatorios']);
        session(['nexus.active_regional_id' => $regional->id]);

        $this->actingAs($viewer);

        Livewire::test(RegionalNoticeManager::class)
            ->set('title', 'Aviso indevido')
            ->set('body', 'Texto')
            ->call('save')
            ->assertForbidden();

        Livewire::test(MinistryServantsPage::class)
            ->set('servant_name', 'José')
            ->call('save')
            ->assertForbidden();

        Livewire::test(MarkingsReportPage::class)
            ->set('servant_name', 'José')
            ->call('saveServant')
            ->assertForbidden();

        $this->assertDatabaseCount('regional_notices', 0);
    }

    public function test_editor_can_save_regional_notice_in_own_scope(): void
    {
        $regional = $this->regional('A');
        $editor = $this->user($regional, ['visualizar_relatorios', 'editar_evento']);
        session(['nexus.active_regional_id' => $regional->id]);

        $this->actingAs($editor);

        Livewire::test(RegionalNoticeManager::class)
            ->set('title', 'Aviso da regional')
            ->set('body', 'Texto operacional')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('regional_notices', [
            'regional_id' => $regional->id,
            'title' => 'Aviso da regional',
        ]);
    }

    public function test_approver_cannot_update_access_request_from_another_regional(): void
    {
        $home = $this->regional('Casa');
        $other = $this->regional('Outra');
        $approver = $this->user($home, ['aprovar_acesso']);
        $request = AccessRequest::query()->create([
            'name' => 'Pedido alheio',
            'email' => 'alheio@example.com',
            'status' => 'pending',
            'regional_id' => $other->id,
        ]);

        $this->actingAs($approver);

        $this->assertFalse($approver->can('update', $request));

        Livewire::test(AccessRequestList::class)
            ->set('review_note', 'não é desta regional')
            ->call('approve', $request->id)
            ->assertForbidden();

        $this->assertSame('pending', $request->fresh()->status);
    }

    public function test_event_without_regional_is_invisible_to_scoped_user(): void
    {
        $regional = $this->regional('A');
        $user = $this->user($regional, ['visualizar_evento']);
        $event = Event::query()->create([
            'title' => 'Sem dono',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
            'status' => 'published',
        ]);

        $this->assertFalse($user->can('view', $event));
        $this->assertFalse($user->can('update', $event));
    }

    /**
     * @param  list<string>  $permissions
     */
    protected function user(Regional $regional, array $permissions): User
    {
        $user = User::factory()->create(['regional_id' => $regional->id]);
        $user->givePermissionTo($permissions);

        return $user;
    }

    protected function regional(string $name): Regional
    {
        return Regional::query()->create([
            'name' => 'Regional '.$name,
            'slug' => 'regional-'.strtolower($name).'-'.uniqid(),
            'is_active' => true,
        ]);
    }
}
