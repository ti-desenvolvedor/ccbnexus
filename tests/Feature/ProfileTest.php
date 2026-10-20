<?php

namespace Tests\Feature;

use App\Livewire\Profile\UpdateAccountPreferences;
use App\Livewire\Profile\UpdateProfilePhoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile');

        $response
            ->assertOk()
            ->assertSee(__('Perfil'))
            ->assertSee(__('Personalizar foto'))
            ->assertSee(__('Itens por página nas listagens'))
            ->assertSeeVolt('profile.update-profile-information-form')
            ->assertSeeVolt('profile.update-password-form')
            ->assertSeeVolt('profile.delete-user-form');
    }

    public function test_user_can_upload_and_remove_profile_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $this->actingAs($user);

        $file = UploadedFile::fake()->image('avatar.jpg', 200, 200);

        Livewire::test(UpdateProfilePhoto::class)
            ->set('photo', $file)
            ->call('savePhoto')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertTrue($user->hasProfilePhoto());
        Storage::disk('public')->assertExists($user->profile_photo_path);

        Livewire::test(UpdateProfilePhoto::class)
            ->call('removePhoto')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertNull($user->profile_photo_path);
        $this->assertFalse($user->hasProfilePhoto());
    }

    public function test_user_can_save_custom_list_per_page_preference(): void
    {
        $user = User::factory()->create(['list_per_page' => 20]);

        $this->actingAs($user);

        Livewire::test(UpdateAccountPreferences::class)
            ->set('list_per_page', 35)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(35, $user->fresh()->resolvedListPerPage());
    }

    public function test_list_per_page_is_clamped_to_configured_bounds(): void
    {
        $user = User::factory()->create(['list_per_page' => 500]);

        $this->assertSame(200, $user->resolvedListPerPage());
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('profile.update-profile-information-form')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->call('updateProfileInformation');

        $component
            ->assertHasNoErrors()
            ->assertNoRedirect();

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('profile.update-profile-information-form')
            ->set('name', 'Test User')
            ->set('email', $user->email)
            ->call('updateProfileInformation');

        $component
            ->assertHasNoErrors()
            ->assertNoRedirect();

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('profile.delete-user-form')
            ->set('password', 'password')
            ->call('deleteUser');

        $component
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('profile.delete-user-form')
            ->set('password', 'wrong-password')
            ->call('deleteUser');

        $component
            ->assertHasErrors('password')
            ->assertNoRedirect();

        $this->assertNotNull($user->fresh());
    }
}
