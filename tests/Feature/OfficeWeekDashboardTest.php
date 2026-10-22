<?php

namespace Tests\Feature;

use App\Livewire\Agenda\ApprovalsQueue;
use App\Livewire\Agenda\EventList;
use App\Livewire\Dashboard\Metrics;
use App\Models\Approval;
use App\Models\BaptismListReport;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Location;
use App\Models\MinistryServant;
use App\Models\Regional;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OfficeWeekDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_opens_on_this_weeks_work_not_a_module_index(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        $regional = Regional::query()->create([
            'name' => 'Regional Embu das Artes',
            'slug' => 'embu-das-artes',
            'is_active' => true,
        ]);
        $type = EventType::query()->create([
            'name' => 'Culto',
            'slug' => 'culto',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $location = Location::query()->create([
            'name' => 'Casa Central',
            'regional_id' => $regional->id,
        ]);

        Event::query()->create([
            'regional_id' => $regional->id,
            'location_id' => $location->id,
            'title' => 'Culto de domingo',
            'event_type_id' => $type->id,
            'starts_at' => Carbon::now()->startOfWeek(Carbon::SUNDAY)->addDay()->setTime(19, 0),
            'ends_at' => Carbon::now()->startOfWeek(Carbon::SUNDAY)->addDay()->setTime(21, 0),
            'status' => 'published',
            'is_occurrence' => false,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Esta semana')
            ->assertSee('Painel')
            ->assertDontSee('Organização — regionais')
            ->assertDontSee('já nasce');

        $outra = Regional::query()->create([
            'name' => 'Regional sem culto',
            'slug' => 'sem-culto',
            'is_active' => true,
        ]);

        Livewire::actingAs($user)
            ->test(Metrics::class)
            ->call('refresh')
            ->assertSee('Culto de domingo')
            ->assertSee('Casa Central')
            ->assertSee('Publicado')
            ->assertSee('Lista de batismos')
            ->assertDontSee('Regionais ativas')
            ->tap(fn () => session(['nexus.active_regional_id' => $outra->id]))
            ->dispatch('nexus-context-updated')
            ->assertSee('Nenhum culto nesta semana em Regional sem culto')
            ->assertDontSee('Culto de domingo');
    }

    public function test_dashboard_summarizes_registry_upcoming_work_and_the_latest_lists(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        $regional = Regional::query()->create([
            'name' => 'Regional Embu das Artes',
            'slug' => 'embu-listas',
            'is_active' => true,
        ]);
        $type = EventType::query()->create([
            'name' => 'Ensaio',
            'slug' => 'ensaio-painel',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $baptism = EventType::query()->create([
            'name' => 'Batismo',
            'slug' => 'batismo',
            'is_active' => true,
            'sort_order' => 2,
        ]);
        Event::query()->create([
            'regional_id' => $regional->id,
            'title' => 'Batismo TBS',
            'event_type_id' => $baptism->id,
            'starts_at' => now()->startOfWeek(Carbon::SUNDAY)->addDay()->setTime(18, 30),
            'ends_at' => now()->startOfWeek(Carbon::SUNDAY)->addDay()->setTime(20, 0),
            'status' => 'published',
            'is_occurrence' => false,
            'created_by' => $user->id,
        ]);
        Event::query()->create([
            'regional_id' => $regional->id,
            'title' => 'Ensaio da semana que vem',
            'event_type_id' => $type->id,
            'starts_at' => now()->startOfWeek(Carbon::SUNDAY)->addDays(8)->setTime(19, 0),
            'ends_at' => now()->startOfWeek(Carbon::SUNDAY)->addDays(8)->setTime(21, 0),
            'status' => 'published',
            'is_occurrence' => false,
            'created_by' => $user->id,
        ]);
        MinistryServant::query()->create([
            'regional_id' => $regional->id,
            'full_name' => 'João da Silva',
            'name_key' => 'joao da silva',
            'ministry' => 'Ancião',
            'phone' => '11987654321',
        ]);
        MinistryServant::query()->create([
            'regional_id' => $regional->id,
            'full_name' => 'Pedro Souza',
            'name_key' => 'pedro souza',
            'ministry' => 'Diácono',
            'phone' => '11987654322',
        ]);
        MinistryServant::query()->create([
            'regional_id' => $regional->id,
            'full_name' => 'Sem Ministério',
            'name_key' => 'sem ministerio',
            'ministry' => '',
            'phone' => '',
        ]);

        foreach ([
            [2, '2026-10-01', BaptismListReport::STATUS_DRAFT, now()->subDays(3)],
            [3, '2026-10-08', BaptismListReport::STATUS_DRAFT, now()->subDays(2)],
            [4, '2026-10-15', BaptismListReport::STATUS_DRAFT, now()->subDay()],
            [7, '2026-09-01', BaptismListReport::STATUS_READY, now()->subDays(20)],
        ] as [$revision, $issue, $status, $generatedAt]) {
            BaptismListReport::query()->create([
                'regional_id' => $regional->id,
                'issue_date' => $issue,
                'period_start' => $issue,
                'period_end' => '2026-10-31',
                'revision' => $revision,
                'status' => $status,
                'generated_by' => $user->id,
                'generated_at' => $generatedAt,
            ]);
        }

        session(['nexus.active_regional_id' => $regional->id]);

        $component = Livewire::actingAs($user)
            ->test(Metrics::class)
            ->call('refresh')
            ->assertSee('Ensaio da semana que vem')
            ->assertSee('Batismo TBS')
            ->assertSee('Cadastros')
            ->assertSee('Ancião')
            ->assertSee('Diácono')
            ->assertSee('Sem cargo')
            ->assertSee('15/10/2026')
            ->assertSee('08/10/2026')
            ->assertSee('01/10/2026')
            ->assertSee('Revisão 4')
            ->assertSee('Revisão 3')
            ->assertSee('Revisão 2')
            ->assertSee('Revisão 7')
            ->assertSee('Aprovada')
            ->assertSee('Para aprovação')
            ->assertSee('Aprovadas')
            ->assertDontSee('Últimas geradas')
            ->assertDontSee('Últimas confirmadas')
            ->assertDontSee('Confirmada')
            ->tap(function ($component) {
                $baptism = collect($component->get('panel')['weekEvents'])->firstWhere('title', 'Batismo TBS');
                $this->assertNotNull($baptism);
                $this->assertFalse($baptism['showStatus']);
            });
    }

    public function test_event_list_filters_by_portuguese_status_and_names_the_empty_context(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        $regional = Regional::query()->create([
            'name' => 'Regional Embu das Artes',
            'slug' => 'embu-das-artes',
            'is_active' => true,
        ]);
        $type = EventType::query()->create([
            'name' => 'Batismo',
            'slug' => 'batismo',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Event::query()->create([
            'regional_id' => $regional->id,
            'title' => 'Culto publicado',
            'event_type_id' => $type->id,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
            'status' => 'published',
            'is_occurrence' => false,
            'created_by' => $user->id,
        ]);
        Event::query()->create([
            'regional_id' => $regional->id,
            'title' => 'Culto em rascunho',
            'event_type_id' => $type->id,
            'starts_at' => now()->addDays(2),
            'ends_at' => now()->addDays(2)->addHour(),
            'status' => 'draft',
            'is_occurrence' => false,
            'created_by' => $user->id,
        ]);

        session(['nexus.active_regional_id' => $regional->id]);

        Livewire::actingAs($user)
            ->test(EventList::class)
            ->set('view', 'list')
            ->set('listMode', 'series')
            ->set('statusFilter', 'draft')
            ->assertSee('Culto em rascunho')
            ->assertSee('Rascunho')
            ->assertDontSee('Culto publicado')
            ->assertDontSee('Escolha «Regional Embu das Artes»');
    }

    public function test_each_approval_keeps_its_own_note(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        $regional = Regional::query()->create([
            'name' => 'Regional Embu das Artes',
            'slug' => 'embu-das-artes',
            'is_active' => true,
        ]);
        $type = EventType::query()->create([
            'name' => 'Culto',
            'slug' => 'culto-aprovacao',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $location = Location::query()->create([
            'name' => 'Casa Central',
            'regional_id' => $regional->id,
        ]);
        $event = Event::query()->create([
            'regional_id' => $regional->id,
            'location_id' => $location->id,
            'title' => 'Culto para aprovar',
            'event_type_id' => $type->id,
            'starts_at' => now()->addDay()->setTime(19, 30),
            'ends_at' => now()->addDay()->setTime(21, 0),
            'status' => 'pending_approval',
            'is_occurrence' => false,
            'created_by' => $user->id,
        ]);
        $approval = Approval::query()->create([
            'approvable_type' => Event::class,
            'approvable_id' => $event->id,
            'status' => 'pending',
            'requested_by_user_id' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(ApprovalsQueue::class)
            ->assertSee('Culto para aprovar')
            ->assertSee('Casa Central')
            ->assertSee('Pendente')
            ->call('approve', $approval->id)
            ->assertHasErrors(['notes.'.$approval->id])
            ->set('notes.'.$approval->id, 'Boletim conferido.')
            ->call('approve', $approval->id)
            ->assertHasNoErrors();

        $this->assertSame('published', $event->fresh()->status);
        $this->assertSame('approved', $approval->fresh()->status);
        $this->assertSame('Boletim conferido.', $approval->fresh()->decision_note);
    }
}
