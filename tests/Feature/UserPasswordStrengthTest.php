<?php

namespace Tests\Feature;

use App\Livewire\Users\UserList;
use App\Models\User;
use App\Support\PasswordStrength;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserPasswordStrengthTest extends TestCase
{
    use RefreshDatabase;

    public function test_level_marks_missing_symbol_or_number_as_weak(): void
    {
        $this->assertSame(PasswordStrength::WEAK, PasswordStrength::level('sem-numero'));
        $this->assertSame(PasswordStrength::WEAK, PasswordStrength::level('Senha1234'));
        $this->assertSame(PasswordStrength::STRONG, PasswordStrength::level('Igreja2026!'));
    }

    public function test_user_list_shows_password_level_and_filters_who_must_change(): void
    {
        $viewer = User::factory()->create();
        $viewer->forceFill(['is_super_admin' => true])->save();
        $weak = User::factory()->create([
            'name' => 'Conta fraca',
            'password_strength' => PasswordStrength::WEAK,
        ]);
        $unknown = User::factory()->create([
            'name' => 'Conta antiga',
            'password_strength' => PasswordStrength::UNKNOWN,
        ]);
        $strong = User::factory()->create([
            'name' => 'Conta adequada',
            'password_strength' => PasswordStrength::STRONG,
        ]);

        $this->actingAs($viewer);

        Livewire::test(UserList::class)
            ->assertSee('Conta fraca')
            ->assertSee('Conta antiga')
            ->assertSee('Conta adequada')
            ->assertSee('Fraca')
            ->assertSee('Não verificada')
            ->assertSee('Adequada')
            ->assertSee('Peça a troca')
            ->set('strength', 'review')
            ->assertSee($weak->name)
            ->assertSee($unknown->name)
            ->assertDontSee($strong->name)
            ->set('strength', 'strong')
            ->assertSee($strong->name)
            ->assertDontSee($weak->name)
            ->assertDontSee($unknown->name);
    }
}
