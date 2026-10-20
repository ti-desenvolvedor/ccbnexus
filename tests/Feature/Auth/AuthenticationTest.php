<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response
            ->assertOk()
            ->assertSeeVolt('pages.auth.login');
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password');

        $component->call('login');

        $component
            ->assertHasNoErrors()
            ->assertRedirect(RouteServiceProvider::HOME);

        $this->assertAuthenticated();
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'wrong-password');

        $component->call('login');

        $component
            ->assertHasErrors()
            ->assertNoRedirect();

        $this->assertGuest();
    }

    public function test_captcha_is_required_after_repeated_failures_and_login_locks(): void
    {
        $user = User::factory()->create();

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'errada');

        $component->call('login');
        $component->call('login');

        $component
            ->assertHasErrors(['form.email'])
            ->assertSee('Código da imagem');

        $component
            ->set('form.password', 'password')
            ->call('login')
            ->assertHasErrors(['form.captcha'])
            ->assertNoRedirect();

        $this->assertGuest();

        $code = decrypt(session('login.captcha'));

        $component
            ->set('form.captcha', $code)
            ->set('form.password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(RouteServiceProvider::HOME);

        $this->assertAuthenticated();
    }

    public function test_login_locks_for_a_cooldown_after_too_many_failures(): void
    {
        config([
            'nexus.auth.max_attempts' => 3,
            'nexus.auth.captcha_after' => 2,
            'nexus.auth.lock_seconds' => 900,
        ]);

        $user = User::factory()->create();

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'errada');

        $component->call('login');
        $component->call('login');
        $component->call('login');

        $component
            ->assertHasErrors(['form.email'])
            ->assertSee(__('auth.throttle', ['seconds' => 900, 'minutes' => 15]), false);

        $component
            ->set('form.password', 'password')
            ->set('form.captcha', 'ABCDE')
            ->call('login')
            ->assertHasErrors(['form.email'])
            ->assertNoRedirect();

        $this->assertGuest();
    }

    public function test_navigation_menu_can_be_rendered(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get('/dashboard');

        $response
            ->assertOk()
            ->assertSee(__('Dashboard'), false);
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->post(route('logout'));

        $response->assertRedirect('/');

        $this->assertGuest();
    }
}
