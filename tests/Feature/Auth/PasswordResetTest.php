<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Volt\Volt;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response
            ->assertSeeVolt('pages.auth.forgot-password')
            ->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        Volt::test('pages.auth.forgot-password')
            ->set('email', $user->email)
            ->call('sendPasswordResetLink');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_unknown_email_gets_the_same_message_and_no_mail(): void
    {
        Notification::fake();

        $message = __('auth.password_reset_sent');

        Volt::test('pages.auth.forgot-password')
            ->set('email', 'nao-existe@example.com')
            ->call('sendPasswordResetLink')
            ->assertHasNoErrors()
            ->assertSee($message)
            ->assertDontSee('nao-existe@example.com')
            ->assertDontSee('Não conseguimos encontrar');

        Notification::assertNothingSent();
    }

    public function test_repeated_reset_requests_keep_the_same_message(): void
    {
        Notification::fake();
        RateLimiter::clear('password-reset-ip|127.0.0.1');

        $user = User::factory()->create();
        $message = __('auth.password_reset_sent');
        $component = Volt::test('pages.auth.forgot-password');

        $component->set('email', $user->email)
            ->call('sendPasswordResetLink')
            ->assertSee($message)
            ->assertDontSee('Não conseguimos encontrar')
            ->assertDontSee('espere antes de tentar');

        $component->set('email', $user->email)
            ->call('sendPasswordResetLink')
            ->assertSee($message)
            ->assertDontSee('espere antes de tentar');

        Notification::assertSentToTimes($user, ResetPassword::class, 1);

        RateLimiter::clear('password-reset-ip|127.0.0.1');
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        Volt::test('pages.auth.forgot-password')
            ->set('email', $user->email)
            ->call('sendPasswordResetLink');

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
            $response = $this->get('/reset-password/'.$notification->token);

            $response
                ->assertSeeVolt('pages.auth.reset-password')
                ->assertStatus(200);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        Volt::test('pages.auth.forgot-password')
            ->set('email', $user->email)
            ->call('sendPasswordResetLink');

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $component = Volt::test('pages.auth.reset-password', ['token' => $notification->token])
                ->set('email', $user->email)
                ->set('password', 'Nova-senha1')
                ->set('password_confirmation', 'Nova-senha1');

            $component->call('resetPassword');

            $component
                ->assertRedirect('/login')
                ->assertHasNoErrors();

            return true;
        });
    }

    public function test_authenticated_user_can_open_the_reset_password_screen(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/reset-password/token-de-teste?email='.urlencode($user->email))
            ->assertOk()
            ->assertSee('Nova senha')
            ->assertSee('Você já está no painel');
    }

    public function test_login_screen_shows_password_recovery_link(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Esqueci minha senha')
            ->assertSee(route('password.request'), false);

        $this->get('/forgot-password')
            ->assertOk()
            ->assertSee('Recuperar senha')
            ->assertSee('Voltar ao login');
    }
}
