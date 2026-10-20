<?php

namespace Tests\Feature;

use App\Livewire\Secretaria\MinistryServantsPage;
use App\Models\Administration;
use App\Models\EventRoleAssignment;
use App\Models\MinistryServant;
use App\Models\PrayerHouse;
use App\Models\PublicDepartment;
use App\Models\PublicPosition;
use App\Models\Regional;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MinistryServantsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_servant_with_comum_and_attendance_churches_then_edits_and_rejects_duplicate(): void
    {
        $regional = Regional::query()->create([
            'name' => 'Regional Embu',
            'slug' => 'regional-embu',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'regional_id' => $regional->id,
            'is_super_admin' => true,
        ]);
        $administration = Administration::query()->create([
            'regional_id' => $regional->id,
            'name' => 'Administração Embu',
            'slug' => 'administracao-embu',
            'is_active' => true,
        ]);
        $comum = $this->house($administration, 'Casa Centro', 'casa-centro');
        $norte = $this->house($administration, 'Casa Norte', 'casa-norte');
        $sul = $this->house($administration, 'Casa Sul', 'casa-sul');
        $office = $this->ministryOffice($regional);

        session(['nexus.active_regional_id' => $regional->id]);
        $this->actingAs($user);

        $component = Livewire::test(MinistryServantsPage::class)
            ->assertSee('Comum')
            ->assertSee('Igrejas de atendimento')
            ->set('servant_name', 'José da Silva')
            ->set('servant_position_id', (string) $office->id)
            ->set('servant_phone', '')
            ->set('comum_prayer_house_id', (string) $comum->id)
            ->set('attendance_prayer_house_ids', [(string) $norte->id, (string) $sul->id])
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('José da Silva')
            ->assertSee('Ancião')
            ->assertSee('Casa Centro')
            ->assertSee('Casa Norte')
            ->assertSee('Casa Sul');

        $servant = MinistryServant::query()->where('full_name', 'José da Silva')->first();
        $this->assertNotNull($servant);
        $this->assertSame('', $servant->phone);
        $this->assertSame($comum->id, $servant->comum_prayer_house_id);
        $this->assertEqualsCanonicalizing(
            [$norte->id, $sul->id],
            $servant->attendanceHouses()->pluck('prayer_houses.id')->all(),
        );
        $this->assertSame(0, EventRoleAssignment::query()->count());

        $component
            ->call('edit', $servant->id)
            ->assertSet('comum_prayer_house_id', (string) $comum->id)
            ->assertSet('attendance_prayer_house_ids', [(string) $norte->id, (string) $sul->id])
            ->set('comum_prayer_house_id', (string) $norte->id)
            ->set('attendance_prayer_house_ids', [(string) $comum->id, (string) $sul->id])
            ->call('save')
            ->assertHasNoErrors();

        $servant->refresh();
        $this->assertSame($norte->id, $servant->comum_prayer_house_id);
        $this->assertEqualsCanonicalizing(
            [$comum->id, $sul->id],
            $servant->attendanceHouses()->pluck('prayer_houses.id')->all(),
        );

        Livewire::test(MinistryServantsPage::class)
            ->set('servant_name', 'Jose da Silva')
            ->set('servant_position_id', (string) $office->id)
            ->set('comum_prayer_house_id', (string) $sul->id)
            ->set('attendance_prayer_house_ids', [(string) $norte->id])
            ->call('save')
            ->assertHasErrors(['servant_name']);

        $this->assertSame(1, MinistryServant::query()->where('regional_id', $regional->id)->count());
        $this->assertNull($servant->fresh()->ordained_on);
    }

    public function test_ordination_date_is_optional_on_create_and_can_be_set_or_cleared(): void
    {
        $regional = Regional::query()->create([
            'name' => 'Regional Embu',
            'slug' => 'regional-embu',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'regional_id' => $regional->id,
            'is_super_admin' => true,
        ]);
        $office = $this->ministryOffice($regional);

        session(['nexus.active_regional_id' => $regional->id]);
        $this->actingAs($user);

        Livewire::test(MinistryServantsPage::class)
            ->assertSee('Data de ordenação')
            ->set('servant_name', 'Maria dos Santos')
            ->set('servant_position_id', (string) $office->id)
            ->set('servant_phone', '')
            ->set('ordained_on', '')
            ->call('save')
            ->assertHasNoErrors();

        $servant = MinistryServant::query()->where('full_name', 'Maria dos Santos')->firstOrFail();
        $this->assertNull($servant->ordained_on);

        Livewire::test(MinistryServantsPage::class)
            ->call('edit', $servant->id)
            ->assertSet('ordained_on', '')
            ->set('ordained_on', '2012-04-08')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('08/04/2012');

        $servant->refresh();
        $this->assertSame('2012-04-08', $servant->ordained_on?->toDateString());

        Livewire::test(MinistryServantsPage::class)
            ->call('edit', $servant->id)
            ->assertSet('ordained_on', '2012-04-08')
            ->set('ordained_on', 'not-a-date')
            ->call('save')
            ->assertHasErrors(['ordained_on']);

        $this->assertSame('2012-04-08', $servant->fresh()->ordained_on?->toDateString());

        Livewire::test(MinistryServantsPage::class)
            ->call('edit', $servant->id)
            ->set('ordained_on', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($servant->fresh()->ordained_on);
    }

    protected function house(Administration $administration, string $name, string $slug): PrayerHouse
    {
        return PrayerHouse::query()->create([
            'administration_id' => $administration->id,
            'name' => $name,
            'slug' => $slug,
            'is_active' => true,
        ]);
    }

    protected function ministryOffice(Regional $regional): PublicPosition
    {
        $department = PublicDepartment::query()->firstOrCreate(
            ['scope' => PublicDepartment::SCOPE_REGIONAL, 'regional_id' => $regional->id, 'slug' => 'ministerio'],
            ['name' => 'Ministério', 'is_active' => true, 'sort_order' => 10],
        );

        return PublicPosition::query()->firstOrCreate(
            ['public_department_id' => $department->id, 'slug' => 'anciao'],
            ['name' => 'Ancião', 'is_active' => true, 'sort_order' => 10],
        );
    }
}
