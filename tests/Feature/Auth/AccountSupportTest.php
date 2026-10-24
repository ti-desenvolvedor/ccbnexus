<?php

namespace Tests\Feature\Auth;

use App\Livewire\Users\UserList;
use App\Models\Regional;
use App\Models\User;
use App\Services\Auth\ImpersonationService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AccountSupportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_scoped_admin_sends_reset_link_without_revealing_the_password(): void
    {
        Notification::fake();

        $regional = $this->regional('A');
        $admin = $this->user($regional, ['gerenciar_usuarios']);
        $target = $this->user($regional, []);

        $this->actingAs($admin);

        Livewire::test(UserList::class)
            ->call('sendPasswordReset', $target->id)
            ->assertSee('Link de redefinição enviado para '.$target->email)
            ->assertDontSee($target->password);

        Notification::assertSentTo($target, ResetPassword::class);
        Notification::assertNotSentTo($admin, ResetPassword::class);

        $activity = Activity::query()->where('event', 'password_reset_link_sent')->first();
        $this->assertNotNull($activity);
        $this->assertSame($admin->id, $activity->causer_id);
        $this->assertSame($target->id, $activity->subject_id);
        $this->assertArrayNotHasKey('password', $activity->properties->toArray());
        $this->assertArrayNotHasKey('token', $activity->properties->toArray());
    }

    public function test_admin_cannot_send_reset_link_outside_the_regional_or_to_a_super_admin(): void
    {
        Notification::fake();

        $home = $this->regional('Casa');
        $other = $this->regional('Outra');
        $admin = $this->user($home, ['gerenciar_usuarios']);
        $neighbor = $this->user($other, []);
        $super = User::factory()->create();
        $super->forceFill(['is_super_admin' => true])->save();

        $this->actingAs($admin);

        Livewire::test(UserList::class)
            ->call('sendPasswordReset', $neighbor->id)
            ->assertForbidden();

        Livewire::test(UserList::class)
            ->call('sendPasswordReset', $super->id)
            ->assertForbidden();

        Notification::assertNothingSent();
    }

    public function test_impersonation_requires_permission_scope_and_justification_and_can_stop(): void
    {
        $regional = $this->regional('A');
        $other = $this->regional('B');
        $manager = $this->user($regional, ['gerenciar_usuarios']);
        $support = $this->user($regional, ['gerenciar_usuarios', 'personificar_usuario']);
        $target = $this->user($regional, []);
        $neighbor = $this->user($other, []);
        $super = User::factory()->create(['name' => 'Conta super']);
        $super->forceFill(['is_super_admin' => true])->save();

        $this->actingAs($manager);
        Livewire::test(UserList::class)
            ->call('askImpersonation', $target->id)
            ->assertForbidden();

        $this->actingAs($support);
        Livewire::test(UserList::class)
            ->call('askImpersonation', $neighbor->id)
            ->assertForbidden();

        Livewire::test(UserList::class)
            ->call('askImpersonation', $target->id)
            ->set('justification', 'curto')
            ->call('startImpersonation')
            ->assertHasErrors('justification');

        $this->assertAuthenticatedAs($support);

        Livewire::test(UserList::class)
            ->call('askImpersonation', $target->id)
            ->set('justification', 'Conferir a fila de reservas desta casa')
            ->call('startImpersonation')
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($target);
        $this->assertSame($support->id, session(ImpersonationService::SESSION_KEY));
        $this->assertIsInt(session(ImpersonationService::SESSION_STARTED_KEY));

        $started = Activity::query()->where('event', 'impersonation_started')->first();
        $this->assertNotNull($started);
        $this->assertSame($support->id, (int) $started->properties['impersonator_id']);
        $this->assertSame('Conferir a fila de reservas desta casa', $started->properties['justification']);

        $nested = false;
        try {
            app(ImpersonationService::class)->start($target, $neighbor, 'Tentativa encadeada de personificação');
        } catch (\Illuminate\Auth\Access\AuthorizationException) {
            $nested = true;
        }
        $this->assertTrue($nested);
        $this->assertAuthenticatedAs($target);

        $this->post(route('impersonation.stop'))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($support);
        $this->assertNull(session(ImpersonationService::SESSION_KEY));
        $this->assertNull(session(ImpersonationService::SESSION_STARTED_KEY));
        $stopped = Activity::query()->where('event', 'impersonation_stopped')->first();
        $this->assertNotNull($stopped);
        $this->assertArrayHasKey('duration_seconds', $stopped->properties->toArray());

        $this->actingAs($super);
        Livewire::test(UserList::class)
            ->call('askImpersonation', $super->id)
            ->assertForbidden();

        Livewire::test(UserList::class)
            ->call('askImpersonation', $target->id)
            ->set('justification', 'Suporte do super-admin nesta regional')
            ->call('startImpersonation')
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($target);
        $this->assertSame($super->id, session(ImpersonationService::SESSION_KEY));
    }

    /**
     * @param  list<string>  $permissions
     */
    private function user(Regional $regional, array $permissions): User
    {
        $user = User::factory()->create(['regional_id' => $regional->id]);
        if ($permissions !== []) {
            $user->givePermissionTo($permissions);
        }

        return $user;
    }

    private function regional(string $name): Regional
    {
        return Regional::query()->create([
            'name' => 'Regional '.$name,
            'slug' => 'regional-'.strtolower($name).'-'.uniqid(),
            'is_active' => true,
        ]);
    }
}
