<?php

namespace Tests\Feature;

use App\Livewire\Agenda\EventList;
use App\Models\Event;
use App\Models\Holiday;
use App\Models\HolidaySyncLog;
use App\Models\Location;
use App\Models\Regional;
use App\Models\User;
use App\Services\Agenda\AgendaYearBook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class HolidayImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_imported_holidays_mark_the_calendar_and_stay_out_of_the_list(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        $location = Location::query()->create([
            'name' => 'Central',
            'city' => 'Embu das Artes',
            'state' => 'SP',
        ]);
        $regional = Regional::query()->create([
            'name' => 'Regional Embu das Artes',
            'slug' => 'embu',
            'is_active' => true,
            'location_id' => $location->id,
        ]);
        Event::query()->create([
            'regional_id' => $regional->id,
            'title' => 'Ensaio de quinta',
            'starts_at' => '2026-07-09 19:00:00',
            'ends_at' => '2026-07-09 21:00:00',
            'status' => 'published',
            'created_by' => $user->id,
        ]);
        session(['nexus.active_regional_id' => $regional->id]);

        $component = Livewire::actingAs($user)
            ->test(EventList::class)
            ->set('cursor', '2026-07-09')
            ->call('importHolidays')
            ->assertSee('cadastrados para Embu das Artes (São Paulo)')
            ->assertSee('Revolução Constitucionalista')
            ->assertSee('Ensaio de quinta')
            ->set('view', 'week')
            ->assertSee('Revolução Constitucionalista')
            ->set('view', 'agenda')
            ->assertSee('Revolução Constitucionalista')
            ->set('view', 'list')
            ->assertDontSee('Revolução Constitucionalista')
            ->set('listMode', 'series')
            ->assertDontSee('Revolução Constitucionalista');

        $stored = Holiday::query()->count();
        $component->call('importHolidays');

        $this->assertSame(0, Event::query()->where('title', 'like', '%Constitucionalista%')->count());
        $this->assertSame($stored, Holiday::query()->count());
        $this->assertSame(1, Holiday::query()->where('source_key', 'state:SP:2026-07-09')->count());
        $this->assertSame(1, HolidaySyncLog::query()->where('status', 'success')->count());
    }

    public function test_state_holiday_follows_the_regional_city_and_another_state_does_not_see_it(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        $sp = $this->regional('Regional Embu', 'embu', 'Embu das Artes', 'SP');
        $rj = $this->regional('Regional Rio', 'rio', 'Rio de Janeiro', 'RJ');

        session(['nexus.active_regional_id' => $sp->id]);
        Livewire::actingAs($user)
            ->test(EventList::class)
            ->set('cursor', '2026-07-01')
            ->call('importHolidays');

        session(['nexus.active_regional_id' => $rj->id]);
        Livewire::actingAs($user)
            ->test(EventList::class)
            ->set('cursor', '2026-07-01')
            ->assertDontSee('Revolução Constitucionalista')
            ->set('cursor', '2026-04-23')
            ->call('importHolidays')
            ->assertSee('São Jorge')
            ->set('view', 'list')
            ->assertDontSee('São Jorge');
    }

    public function test_state_comes_from_a_location_of_the_regional_when_the_regional_has_no_own_address(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        $regional = Regional::query()->create([
            'name' => 'Regional Embu das Artes',
            'slug' => 'embu-locais',
            'is_active' => true,
        ]);
        Location::query()->create([
            'regional_id' => $regional->id,
            'name' => 'Embu das Artes - Central',
            'city' => 'Embu das Artes',
            'state' => 'SP',
        ]);
        session(['nexus.active_regional_id' => $regional->id]);

        Livewire::actingAs($user)
            ->test(EventList::class)
            ->set('cursor', '2026-07-09')
            ->call('importHolidays')
            ->assertSee('Revolução Constitucionalista')
            ->assertSee('Embu das Artes (São Paulo)');
    }

    public function test_missing_state_imports_only_national_holidays(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        $regional = Regional::query()->create([
            'name' => 'Regional sem local',
            'slug' => 'sem-local',
            'is_active' => true,
        ]);
        session(['nexus.active_regional_id' => $regional->id]);

        Livewire::actingAs($user)
            ->test(EventList::class)
            ->set('cursor', '2026-07-09')
            ->call('importHolidays')
            ->assertSee('feriado estadual não aparece')
            ->assertDontSee('Revolução Constitucionalista')
            ->set('cursor', '2026-04-21')
            ->assertSee('Tiradentes');

        $this->assertGreaterThan(0, Holiday::query()->where('scope', 'state')->count());
    }

    public function test_year_book_marks_holiday_dates_without_adding_a_commitment(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        $regional = $this->regional('Regional Embu', 'embu-pdf', 'Embu das Artes', 'SP');
        session(['nexus.active_regional_id' => $regional->id]);

        Livewire::actingAs($user)
            ->test(EventList::class)
            ->set('cursor', '2026-07-09')
            ->call('importHolidays');

        $book = app(AgendaYearBook::class)->compose($user, 2026);
        $july = collect($book['pages'])->first(fn (array $page): bool => ($page['kind'] ?? '') === 'month' && ($page['name'] ?? '') === 'Julho' && ($page['calendar'] ?? []) !== []);
        $marked = collect($july['calendar'])->flatten(1)->first(fn (array $day): bool => $day['inMonth'] && $day['day'] === 9);

        $this->assertTrue($marked['holiday']);
        $this->assertTrue($july['hasHoliday']);
        $this->assertTrue($july['empty']);
    }

    public function test_sync_runs_once_a_month_and_keeps_enem_and_elections_off_the_list(): void
    {
        $this->travelTo('2026-10-19 10:00:00');
        $user = User::factory()->create(['is_super_admin' => true]);
        $regional = $this->regional('Regional Embu', 'embu-sync', 'Embu das Artes', 'SP');
        session(['nexus.active_regional_id' => $regional->id]);

        $this->artisan('nexus:sync-holidays')->assertSuccessful();
        $stored = Holiday::query()->count();
        $this->artisan('nexus:sync-holidays')->assertSuccessful();

        $this->assertSame(1, HolidaySyncLog::query()->where('status', 'success')->count());
        $this->assertSame($stored, Holiday::query()->count());
        $this->assertSame($stored, Holiday::query()->pluck('source_key')->unique()->count());

        Livewire::actingAs($user)
            ->test(EventList::class)
            ->set('cursor', '2026-11-08')
            ->assertSee('ENEM — 1º dia')
            ->set('view', 'list')
            ->assertDontSee('ENEM — 1º dia')
            ->set('view', 'month')
            ->set('cursor', '2026-10-04')
            ->assertSee('Eleições gerais — 1º turno')
            ->set('view', 'list')
            ->assertDontSee('Eleições gerais — 1º turno');

        $this->travelTo('2026-11-02 10:00:00');
        $this->artisan('nexus:sync-holidays')->assertSuccessful();
        $this->assertSame(2, HolidaySyncLog::query()->where('status', 'success')->count());
        $this->travelBack();
    }

    public function test_brasilapi_fills_the_year_without_optional_days_or_a_second_call(): void
    {
        config(['nexus.holiday_sync.driver' => 'brasilapi']);
        Http::fake([
            'https://brasilapi.com.br/api/feriados/v1/*' => Http::response([
                ['date' => '2026-01-01', 'name' => 'Confraternização mundial', 'type' => 'national'],
                ['date' => '2026-02-16', 'name' => 'Carnaval', 'type' => 'national'],
            ]),
        ]);
        $this->travelTo('2026-10-19 10:00:00');

        $this->artisan('nexus:sync-holidays')->assertSuccessful();

        $this->assertTrue(Holiday::query()->where('source_key', 'national:2026-01-01')->where('name', 'Confraternização mundial')->exists());
        $this->assertFalse(Holiday::query()->where('name', 'Carnaval')->exists());
        $this->assertTrue(Holiday::query()->where('source_key', 'event:enem:2026-11-08')->exists());
        $this->assertSame('brasilapi', HolidaySyncLog::query()->value('source'));
        Http::assertSentCount(2);

        $this->artisan('nexus:sync-holidays')->assertSuccessful();
        Http::assertSentCount(2);
        $this->travelBack();
    }

    private function regional(string $name, string $slug, string $city, string $state): Regional
    {
        $location = Location::query()->create([
            'name' => $city,
            'city' => $city,
            'state' => $state,
        ]);

        return Regional::query()->create([
            'name' => $name,
            'slug' => $slug,
            'is_active' => true,
            'location_id' => $location->id,
        ]);
    }
}
