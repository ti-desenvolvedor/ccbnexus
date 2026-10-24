<?php

namespace Tests\Feature;

use App\Livewire\Agenda\EventList;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Regional;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AgendaViewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_views_keep_filters_and_pdf_follows_the_open_view(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $regional = Regional::query()->create([
            'name' => 'Regional A',
            'slug' => 'regional-a',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'regional_id' => $regional->id,
            'is_super_admin' => true,
        ]);
        $parent = Event::query()->create([
            'regional_id' => $regional->id,
            'title' => 'Serie pai',
            'starts_at' => '2026-09-01 19:00:00',
            'ends_at' => '2026-09-01 21:00:00',
            'status' => 'published',
            'is_occurrence' => false,
            'created_by' => $user->id,
        ]);
        Event::query()->create([
            'regional_id' => $regional->id,
            'title' => 'Ocorrencia de outubro',
            'starts_at' => '2026-10-20 19:00:00',
            'ends_at' => '2026-10-20 21:00:00',
            'status' => 'draft',
            'is_occurrence' => true,
            'parent_event_id' => $parent->id,
            'created_by' => $user->id,
        ]);

        session(['nexus.active_regional_id' => $regional->id]);

        $component = Livewire::actingAs($user)
            ->test(EventList::class)
            ->set('search', 'Ocorrencia')
            ->set('cursor', '2026-10-20')
            ->assertSee('Calendário')
            ->assertSee('Lista')
            ->assertSee('Mensal')
            ->assertSee('Agenda')
            ->assertSee('Gerar PDF')
            ->assertSeeHtml('table-fixed')
            ->assertDontSee('Cancelado')
            ->assertDontSee('Rejeitado')
            ->assertSee('Ocorrencia de outubro')
            ->set('view', 'week')
            ->assertDontSee('Gerar PDF')
            ->assertSet('search', 'Ocorrencia')
            ->set('view', 'list')
            ->set('listMode', 'series')
            ->set('search', '')
            ->assertDontSee('Anterior')
            ->assertSee('Serie pai')
            ->assertSee('Séries da regional, não o mês.')
            ->set('view', 'agenda')
            ->assertDontSee('Gerar PDF')
            ->assertSee('Serie pai')
            ->set('listMode', 'occurrences')
            ->assertSee('Gerar PDF')
            ->assertSeeHtml('layout=agenda');

        $html = $component->html();
        $this->assertStringContainsString('/agenda/events/'.$parent->id.'/edit', $html);
    }

    public function test_pdf_button_stays_disabled_without_one_regional(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Regional::query()->create(['name' => 'Regional A', 'slug' => 'regional-a', 'is_active' => true]);
        Regional::query()->create(['name' => 'Regional B', 'slug' => 'regional-b', 'is_active' => true]);
        $user = User::factory()->create(['is_super_admin' => true]);

        Livewire::actingAs($user)
            ->test(EventList::class)
            ->assertSee('Selecione uma regional no topo para gerar o PDF')
            ->assertSeeHtml('aria-disabled="true"');
    }

    public function test_pending_events_are_listed_on_the_calendar_and_agenda(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $regional = Regional::query()->create([
            'name' => 'Regional A',
            'slug' => 'regional-a',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'regional_id' => $regional->id,
            'is_super_admin' => true,
        ]);
        Event::query()->create([
            'regional_id' => $regional->id,
            'title' => 'Culto para aprovar',
            'starts_at' => '2026-10-12 19:00:00',
            'ends_at' => '2026-10-12 21:00:00',
            'status' => 'pending_approval',
            'created_by' => $user->id,
        ]);
        session(['nexus.active_regional_id' => $regional->id]);

        Livewire::actingAs($user)
            ->test(EventList::class)
            ->set('cursor', '2026-10-12')
            ->assertSee('Para aprovar')
            ->assertSee('Culto para aprovar')
            ->assertSee('· Pendente')
            ->set('view', 'agenda')
            ->assertSee('Pendente')
            ->set('view', 'week')
            ->assertSee('· Pendente');
    }

    public function test_series_and_type_filters_stay_available_in_every_view(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $regional = Regional::query()->create([
            'name' => 'Regional A',
            'slug' => 'regional-a',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'regional_id' => $regional->id,
            'is_super_admin' => true,
        ]);
        $baptism = EventType::query()->create([
            'name' => 'Batismo',
            'slug' => 'batismo',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $youth = EventType::query()->create([
            'name' => 'Mocidade',
            'slug' => 'mocidade',
            'is_active' => true,
            'sort_order' => 2,
        ]);
        Event::query()->create([
            'regional_id' => $regional->id,
            'title' => 'Serie de batismo',
            'event_type_id' => $baptism->id,
            'starts_at' => '2026-10-04 18:30:00',
            'ends_at' => '2026-10-04 20:30:00',
            'status' => 'published',
            'is_occurrence' => false,
            'created_by' => $user->id,
        ]);
        Event::query()->create([
            'regional_id' => $regional->id,
            'title' => 'Serie da mocidade',
            'event_type_id' => $youth->id,
            'starts_at' => '2026-10-11 14:00:00',
            'ends_at' => '2026-10-11 16:00:00',
            'status' => 'published',
            'is_occurrence' => false,
            'created_by' => $user->id,
        ]);
        session(['nexus.active_regional_id' => $regional->id]);

        $component = Livewire::actingAs($user)
            ->test(EventList::class)
            ->set('view', 'month')
            ->assertSee('Ocorrências')
            ->assertSee('Séries')
            ->assertSee('Tipo')
            ->set('listMode', 'series')
            ->set('typeFilter', (string) $baptism->id)
            ->assertSee('Serie de batismo')
            ->assertDontSee('Serie da mocidade')
            ->set('view', 'week')
            ->assertSee('Serie de batismo')
            ->assertDontSee('Serie da mocidade')
            ->assertDontSee('Anterior')
            ->set('view', 'agenda')
            ->assertSee('Serie de batismo')
            ->set('listMode', 'occurrences')
            ->set('typeFilter', '0')
            ->set('cursor', '2026-10-11')
            ->assertSee('Anterior');

        $this->assertSame('agenda', $component->get('view'));
    }
}
