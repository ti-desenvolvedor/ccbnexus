<?php

namespace Tests\Feature\Auth;

use App\Livewire\Users\UserForm;
use App\Models\User;
use App\Services\Auth\PwnedPasswordService;
use App\Support\PasswordStrength;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Tests\TestCase;

class PasswordPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_accepts_medium_and_strong_and_rejects_weak_or_leaked(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Volt::test('profile.update-password-form')
            ->set('current_password', 'password')
            ->set('password', 'Nova-senha1')
            ->set('password_confirmation', 'Nova-senha1')
            ->call('updatePassword')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertSame(PasswordStrength::MEDIUM, $user->password_strength);
        $this->assertTrue(Hash::check('Nova-senha1', $user->password));

        Volt::test('profile.update-password-form')
            ->set('current_password', 'Nova-senha1')
            ->set('password', 'CcbNexus-2026!')
            ->set('password_confirmation', 'CcbNexus-2026!')
            ->call('updatePassword')
            ->assertHasNoErrors();

        $this->assertSame(PasswordStrength::STRONG, $user->refresh()->password_strength);

        Volt::test('profile.update-password-form')
            ->set('current_password', 'CcbNexus-2026!')
            ->set('password', 'senha1234')
            ->set('password_confirmation', 'senha1234')
            ->call('updatePassword')
            ->assertHasErrors(['password']);

        Volt::test('profile.update-password-form')
            ->set('current_password', 'CcbNexus-2026!')
            ->set('password', 'nova-senha1')
            ->set('password_confirmation', 'nova-senha1')
            ->call('updatePassword')
            ->assertHasErrors(['password']);

        $this->assertTrue(Hash::check('CcbNexus-2026!', $user->refresh()->password));

        $this->fakeLeak('CcbNexus-2026!');

        Volt::test('profile.update-password-form')
            ->set('current_password', 'CcbNexus-2026!')
            ->set('password', 'CcbNexus-2026!')
            ->set('password_confirmation', 'CcbNexus-2026!')
            ->call('updatePassword')
            ->assertHasErrors(['password']);
    }

    public function test_reset_rejects_a_leaked_password(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->fakeLeak('CcbNexus-2026!');

        Volt::test('pages.auth.forgot-password')
            ->set('email', $user->email)
            ->call('sendPasswordResetLink');

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            Volt::test('pages.auth.reset-password', ['token' => $notification->token])
                ->set('email', $user->email)
                ->set('password', 'CcbNexus-2026!')
                ->set('password_confirmation', 'CcbNexus-2026!')
                ->call('resetPassword')
                ->assertHasErrors(['password']);

            return true;
        });

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }

    public function test_user_form_rejects_weak_and_stores_strong(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_super_admin' => true])->save();
        $this->actingAs($admin);

        Livewire::test(UserForm::class)
            ->set('name', 'Pessoa nova')
            ->set('email', 'pessoa.nova@example.com')
            ->set('password', 'senha1234')
            ->set('password_confirmation', 'senha1234')
            ->call('save')
            ->assertHasErrors(['password']);

        $this->assertDatabaseMissing('users', ['email' => 'pessoa.nova@example.com']);

        Livewire::test(UserForm::class)
            ->set('name', 'Pessoa nova')
            ->set('email', 'pessoa.nova@example.com')
            ->set('password', 'CcbNexus-2026!')
            ->set('password_confirmation', 'CcbNexus-2026!')
            ->call('save')
            ->assertHasNoErrors();

        $created = User::query()->where('email', 'pessoa.nova@example.com')->first();
        $this->assertNotNull($created);
        $this->assertSame(PasswordStrength::STRONG, $created->password_strength);
        $this->assertTrue(Hash::check('CcbNexus-2026!', $created->password));
    }

    public function test_password_check_reports_level_and_leak_without_echoing_the_password(): void
    {
        $weak = $this->postJson(route('password.check'), ['password' => 'senha1234']);
        $weak->assertOk()->assertJson([
            'level' => PasswordStrength::WEAK,
            'leaked' => false,
            'label' => 'Fraca',
        ]);
        $this->assertStringNotContainsString('senha1234', $weak->getContent());

        $this->fakeLeak('CcbNexus-2026!');

        $leaked = $this->postJson(route('password.check'), ['password' => 'CcbNexus-2026!']);
        $leaked->assertOk()->assertJson([
            'level' => PasswordStrength::STRONG,
            'leaked' => true,
            'label' => 'Vazada',
        ]);
        $this->assertStringNotContainsString('CcbNexus-2026!', $leaked->getContent());
    }

    private function fakeLeak(string $password): void
    {
        $fake = new class($password) extends PwnedPasswordService
        {
            public function __construct(private string $compromised)
            {
            }

            public function leaked(string $password): ?bool
            {
                return $password === $this->compromised;
            }
        };

        $this->app->instance(PwnedPasswordService::class, $fake);
        $this->app->instance(UncompromisedVerifier::class, $fake);
    }
}
