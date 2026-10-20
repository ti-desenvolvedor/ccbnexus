<?php

namespace Tests\Feature;

use App\Livewire\Context\OrganizationalContextSelector;
use App\Livewire\Secretaria\BaptismListReportPage;
use App\Models\BaptismListReport;
use App\Models\BaptismListReportItem;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Regional;
use App\Models\RegionalNotice;
use App\Models\User;
use App\Services\BaptismListReportBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Database\Seeders\CcbReferenceDataSeeder;
use Database\Seeders\EventTypeAudiencePresetSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BaptismListReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_builder_includes_bulletin_events_and_excludes_whatsapp_only(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(CcbReferenceDataSeeder::class);

        $regional = Regional::query()->create([
            'name' => 'Regional Teste',
            'slug' => 'regional-teste',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'regional_id' => $regional->id,
            'is_super_admin' => true,
        ]);

        $batismo = EventType::query()->where('slug', 'batismo')->firstOrFail();
        $ago = EventType::query()->where('slug', 'ago')->firstOrFail();

        Event::query()->create([
            'regional_id' => $regional->id,
            'title' => 'Batismo boletim',
            'event_type_id' => $batismo->id,
            'starts_at' => Carbon::parse('2026-10-20 18:30:00'),
            'ends_at' => Carbon::parse('2026-10-20 20:30:00'),
            'status' => 'published',
            'include_on_baptism_list' => true,
            'whatsapp_enabled' => false,
            'created_by' => $user->id,
        ]);

        Event::query()->create([
            'regional_id' => $regional->id,
            'title' => 'AGO só WhatsApp',
            'event_type_id' => $ago->id,
            'starts_at' => Carbon::parse('2026-10-21 19:30:00'),
            'ends_at' => Carbon::parse('2026-10-21 21:30:00'),
            'status' => 'published',
            'include_on_baptism_list' => false,
            'whatsapp_enabled' => true,
            'created_by' => $user->id,
        ]);

        $report = app(BaptismListReportBuilder::class)->generateOrSync(
            $regional->id,
            Carbon::parse('2026-10-15'),
            $user,
        );

        $titles = $report->items->where('source_type', 'event')->pluck('payload.title')->all();
        $this->assertContains('Batismo boletim', $titles);
        $this->assertNotContains('AGO só WhatsApp', $titles);
        $this->assertTrue($report->items->where('section_key', 'batismos')->isNotEmpty());
    }

    public function test_builder_preserves_hidden_visibility_on_resync(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(CcbReferenceDataSeeder::class);

        $regional = Regional::query()->create([
            'name' => 'Regional Vis',
            'slug' => 'regional-vis',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'regional_id' => $regional->id,
            'is_super_admin' => true,
        ]);
        $batismo = EventType::query()->where('slug', 'batismo')->firstOrFail();

        $event = Event::query()->create([
            'regional_id' => $regional->id,
            'title' => 'Batismo ocultável',
            'event_type_id' => $batismo->id,
            'starts_at' => Carbon::parse('2026-11-01 18:30:00'),
            'ends_at' => Carbon::parse('2026-11-01 20:30:00'),
            'status' => 'published',
            'include_on_baptism_list' => true,
            'created_by' => $user->id,
        ]);

        $builder = app(BaptismListReportBuilder::class);
        $report = $builder->generateOrSync($regional->id, Carbon::parse('2026-10-15'), $user);
        $item = $report->items->firstWhere('source_id', $event->id);
        $this->assertNotNull($item);
        $item->update(['is_visible' => false]);

        $report2 = $builder->generateOrSync($regional->id, Carbon::parse('2026-10-15'), $user);
        $item2 = $report2->items->firstWhere('source_id', $event->id);
        $this->assertNotNull($item2);
        $this->assertFalse($item2->is_visible);
    }

    public function test_location_label_does_not_repeat_a_city_already_in_the_name(): void
    {
        $this->assertSame(
            'Vila Iasi – Taboão da Serra',
            BaptismListReportBuilder::locationLabel('Vila Iasi – Taboão da Serra', 'Taboão da Serra'),
        );
        $this->assertSame(
            'Jardim Maria Rosa - Taboão da Serra',
            BaptismListReportBuilder::locationLabel('Jardim Maria Rosa - Taboão da Serra', 'Taboão da Serra'),
        );
        $this->assertSame(
            'Vila Iasi – Taboão da Serra',
            BaptismListReportBuilder::withoutRepeatedCity('Vila Iasi – Taboão da Serra – Taboão da Serra'),
        );
        $this->assertSame('Brás – São Paulo', BaptismListReportBuilder::locationLabel('Brás', 'São Paulo'));
        $this->assertSame(
            'Cipó&#8209;Guaçu<br>Embu&#8209;Guaçu',
            BaptismListReportBuilder::pdfLocationHtml('Cipó-Guaçu – Embu-Guaçu – Embu-Guaçu'),
        );
    }

    public function test_secretaria_baptism_list_requires_permission(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('secretaria.baptism-list'))
            ->assertForbidden();

        $user->givePermissionTo('visualizar_relatorios');
        $this->actingAs($user)
            ->get(route('secretaria.baptism-list'))
            ->assertOk();
    }

    public function test_period_end_covers_end_of_third_month(): void
    {
        $end = app(BaptismListReportBuilder::class)->periodEndForIssue(Carbon::parse('2026-10-15'));
        $this->assertSame('2026-12-31', $end->toDateString());
    }

    public function test_section_period_defaults_long_vs_short(): void
    {
        $builder = app(BaptismListReportBuilder::class);
        $issue = Carbon::parse('2026-10-15');
        $periods = $builder->defaultSectionPeriods($issue);

        $this->assertSame('2026-10-15', $periods['batismos']['from']);
        $this->assertSame('2026-12-31', $periods['batismos']['to']);
        $this->assertSame('2026-12-31', $periods['mocidade']['to']);
        $this->assertSame('2026-12-31', $periods['jovens12']['to']);
        $this->assertSame('2026-11-30', $periods['ensaios']['to']);
        $this->assertSame('2026-11-30', $periods['rco']['to']);
        $this->assertSame('2026-12-31', $periods['santa-ceia']['to']);
        $this->assertArrayNotHasKey('organistas', $periods);
        $this->assertSame('2026-11-30', $periods[BaptismListReportBuilder::REGIONAL_MEETINGS_PERIOD_KEY]['to']);
    }

    public function test_builder_respects_short_section_period(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(CcbReferenceDataSeeder::class);

        $regional = Regional::query()->create([
            'name' => 'Regional Periodos',
            'slug' => 'regional-periodos',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'regional_id' => $regional->id,
            'is_super_admin' => true,
        ]);
        $ensaio = EventType::query()->where('slug', 'ensaio-regional')->firstOrFail();
        $batismo = EventType::query()->where('slug', 'batismo')->firstOrFail();

        Event::query()->create([
            'regional_id' => $regional->id,
            'title' => 'Ensaio Nov',
            'event_type_id' => $ensaio->id,
            'starts_at' => Carbon::parse('2026-11-15 09:00:00'),
            'ends_at' => Carbon::parse('2026-11-15 11:00:00'),
            'status' => 'published',
            'include_on_baptism_list' => true,
            'created_by' => $user->id,
        ]);
        Event::query()->create([
            'regional_id' => $regional->id,
            'title' => 'Ensaio Dez fora',
            'event_type_id' => $ensaio->id,
            'starts_at' => Carbon::parse('2026-12-15 09:00:00'),
            'ends_at' => Carbon::parse('2026-12-15 11:00:00'),
            'status' => 'published',
            'include_on_baptism_list' => true,
            'created_by' => $user->id,
        ]);
        Event::query()->create([
            'regional_id' => $regional->id,
            'title' => 'Batismo Dez ok',
            'event_type_id' => $batismo->id,
            'starts_at' => Carbon::parse('2026-12-12 18:30:00'),
            'ends_at' => Carbon::parse('2026-12-12 20:30:00'),
            'status' => 'published',
            'include_on_baptism_list' => true,
            'created_by' => $user->id,
        ]);

        $report = app(BaptismListReportBuilder::class)->generateOrSync(
            $regional->id,
            Carbon::parse('2026-10-15'),
            $user,
        );

        $titles = $report->items->where('source_type', 'event')->pluck('payload.title')->all();
        $this->assertContains('Ensaio Nov', $titles);
        $this->assertNotContains('Ensaio Dez fora', $titles);
        $this->assertContains('Batismo Dez ok', $titles);
        $this->assertSame('2026-11-30', $report->meta['section_periods']['ensaios']['to'] ?? null);
    }

    public function test_builder_includes_standing_and_dated_notices(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(CcbReferenceDataSeeder::class);

        $regional = Regional::query()->create([
            'name' => 'Regional Avisos',
            'slug' => 'regional-avisos',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'regional_id' => $regional->id,
            'is_super_admin' => true,
        ]);

        RegionalNotice::query()->create([
            'regional_id' => $regional->id,
            'title' => 'Alerta à irmandade - Halloween',
            'body' => 'Texto Halloween.',
            'category' => 'circular',
            'valid_from' => '2020-01-01',
            'valid_until' => '2099-12-31',
            'is_active' => true,
            'is_standing' => true,
            'sort_order' => 10,
        ]);
        RegionalNotice::query()->create([
            'regional_id' => $regional->id,
            'title' => 'Aviso só desta lista',
            'body' => 'Específico.',
            'category' => 'geral',
            'valid_from' => '2026-10-01',
            'valid_until' => '2026-10-31',
            'is_active' => true,
            'is_standing' => false,
            'sort_order' => 50,
        ]);
        RegionalNotice::query()->create([
            'regional_id' => $regional->id,
            'title' => 'Aviso fora da vigência',
            'body' => 'Não deve aparecer.',
            'category' => 'geral',
            'valid_from' => '2026-01-01',
            'valid_until' => '2026-01-31',
            'is_active' => true,
            'is_standing' => false,
            'sort_order' => 60,
        ]);

        $report = app(BaptismListReportBuilder::class)->generateOrSync(
            $regional->id,
            Carbon::parse('2026-10-15'),
            $user,
        );

        $titles = $report->items->where('section_key', 'avisos')->pluck('payload.title')->all();
        $this->assertContains('Alerta à irmandade - Halloween', $titles);
        $this->assertContains('Aviso só desta lista', $titles);
        $this->assertNotContains('Aviso fora da vigência', $titles);
    }

    public function test_builder_includes_meeting_events_with_context_and_schedule(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(CcbReferenceDataSeeder::class);
        $this->seed(EventTypeAudiencePresetSeeder::class);

        $regional = Regional::query()->create([
            'name' => 'Regional Reuniões',
            'slug' => 'regional-reunioes',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'regional_id' => $regional->id,
            'is_super_admin' => true,
        ]);
        $organistas = EventType::query()->where('slug', 'reuniao-organistas')->firstOrFail();
        $rrm = EventType::query()->where('slug', 'rrm')->firstOrFail();

        Event::query()->create([
            'regional_id' => $regional->id,
            'title' => 'Organistas EA',
            'description' => '',
            'event_type_id' => $organistas->id,
            'starts_at' => Carbon::parse('2026-11-14 09:00:00'),
            'ends_at' => Carbon::parse('2026-11-14 11:00:00'),
            'status' => 'published',
            'include_on_baptism_list' => true,
            'created_by' => $user->id,
        ]);

        Event::query()->create([
            'regional_id' => $regional->id,
            'title' => 'RRM EA',
            'description' => 'Convocação ministerial da regional.',
            'event_type_id' => $rrm->id,
            'starts_at' => Carbon::parse('2026-11-08 09:00:00'),
            'ends_at' => Carbon::parse('2026-11-08 12:00:00'),
            'status' => 'published',
            'include_on_baptism_list' => true,
            'created_by' => $user->id,
        ]);

        $report = app(BaptismListReportBuilder::class)->generateOrSync(
            $regional->id,
            Carbon::parse('2026-10-15'),
            $user,
        );

        $orgItem = $report->items->firstWhere('section_key', 'organistas');
        $this->assertNotNull($orgItem);
        $this->assertSame('14/11', $orgItem->payload['date'] ?? null);
        $this->assertSame('09:00', $orgItem->payload['time'] ?? null);
        $this->assertStringContainsString('14/11', (string) ($orgItem->payload['description'] ?? ''));
        $this->assertStringContainsString('09:00', (string) ($orgItem->payload['description'] ?? ''));
        $this->assertStringContainsString('haverá essa reunião', (string) ($orgItem->payload['description'] ?? ''));
        $this->assertStringContainsString('organistas', mb_strtolower((string) ($orgItem->payload['description'] ?? '')));
        $this->assertTrue($orgItem->payload['description_from_template'] ?? false);
        $this->assertSame('narrative', $orgItem->payload['report_layout'] ?? null);

        $rrmItem = $report->items->firstWhere('section_key', 'rrm');
        $this->assertNotNull($rrmItem);
        $this->assertStringContainsString('08/11', (string) ($rrmItem->payload['description'] ?? ''));
        $this->assertStringNotContainsString('Convocação ministerial', (string) ($rrmItem->payload['description'] ?? ''));
        $this->assertSame('08/11', $rrmItem->payload['date'] ?? null);
    }

    public function test_baptism_list_follows_the_active_regional(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(CcbReferenceDataSeeder::class);

        $demo = Regional::query()->create([
            'name' => 'Regional demonstração',
            'slug' => 'ccb-demo',
            'is_active' => true,
        ]);
        $embu = Regional::query()->create([
            'name' => 'Regional Embu das Artes',
            'slug' => 'embu-das-artes',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'regional_id' => $embu->id,
            'is_super_admin' => true,
        ]);
        $batismo = EventType::query()->where('slug', 'batismo')->firstOrFail();

        Event::query()->create([
            'regional_id' => $embu->id,
            'title' => 'Batismo Embu',
            'event_type_id' => $batismo->id,
            'starts_at' => Carbon::parse('2026-10-20 18:30:00'),
            'ends_at' => Carbon::parse('2026-10-20 20:30:00'),
            'status' => 'published',
            'include_on_baptism_list' => true,
            'created_by' => $user->id,
        ]);

        session(['nexus.active_regional_id' => $demo->id]);

        $page = Livewire::actingAs($user)->test(BaptismListReportPage::class)
            ->assertSee('Regional demonstração')
            ->assertDontSee('Batismo Embu');

        session(['nexus.active_regional_id' => $embu->id]);

        $page->dispatch('nexus-context-updated')
            ->assertSee('Regional Embu das Artes')
            ->assertSee('Batismo Embu');

        Livewire::actingAs($user)->test(OrganizationalContextSelector::class)
            ->assertSet('regionalId', (string) $embu->id)
            ->set('regionalId', (string) $demo->id)
            ->assertHasNoErrors()
            ->assertDispatched('nexus-context-updated');

        $this->assertSame($demo->id, session('nexus.active_regional_id'));
    }

    public function test_pending_event_appears_in_listing_preview_and_revisions(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(CcbReferenceDataSeeder::class);

        $regional = Regional::query()->create([
            'name' => 'Regional Teste',
            'slug' => 'regional-teste',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'regional_id' => $regional->id,
            'is_super_admin' => true,
        ]);
        $batismo = EventType::query()->where('slug', 'batismo')->firstOrFail();

        Event::query()->create([
            'regional_id' => $regional->id,
            'title' => 'Batismo pendente',
            'event_type_id' => $batismo->id,
            'starts_at' => Carbon::parse('2026-10-20 18:30:00'),
            'ends_at' => Carbon::parse('2026-10-20 20:30:00'),
            'status' => 'pending_approval',
            'include_on_baptism_list' => true,
            'created_by' => $user->id,
        ]);

        $report = app(BaptismListReportBuilder::class)->generateOrSync(
            $regional->id,
            Carbon::parse('2026-10-15'),
            $user,
        );
        $item = $report->items->firstWhere('payload.title', 'Batismo pendente');
        $this->assertNotNull($item);
        $this->assertSame('pending_approval', $item->payload['status'] ?? null);

        session(['nexus.active_regional_id' => $regional->id]);

        Livewire::actingAs($user)
            ->test(BaptismListReportPage::class)
            ->assertSee('Para aprovar')
            ->assertSee('Batismo pendente')
            ->call('setScreen', 'visualizacao')
            ->assertSee('Visualização da lista')
            ->assertSee('Batismo pendente')
            ->call('setScreen', 'versoes')
            ->assertSee('Versões da lista')
            ->assertSee('R00');
    }

    public function test_generic_meeting_marked_for_the_bulletin_is_listed(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(CcbReferenceDataSeeder::class);

        $regional = Regional::query()->create([
            'name' => 'Regional Teste',
            'slug' => 'regional-teste',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'regional_id' => $regional->id,
            'is_super_admin' => true,
        ]);
        $reuniao = EventType::query()->where('slug', 'reuniao')->firstOrFail();

        Event::query()->create([
            'regional_id' => $regional->id,
            'title' => 'Reunião Quadrimestral DEPAC',
            'event_type_id' => $reuniao->id,
            'starts_at' => Carbon::parse('2026-10-30 19:30:00'),
            'ends_at' => Carbon::parse('2026-10-30 21:30:00'),
            'status' => 'published',
            'include_on_baptism_list' => true,
            'description' => 'Comparecimento do ministério e colaboradores da regional.',
            'created_by' => $user->id,
        ]);

        $report = app(BaptismListReportBuilder::class)->generateOrSync(
            $regional->id,
            Carbon::parse('2026-10-19'),
            $user,
        );

        $item = $report->items->firstWhere('payload.title', 'Reunião Quadrimestral DEPAC');
        $this->assertNotNull($item);
        $this->assertSame('reunioes', $item->section_key);

        session(['nexus.active_regional_id' => $regional->id]);

        Livewire::actingAs($user)
            ->test(BaptismListReportPage::class)
            ->assertSee('Reuniões Regionais - Teste')
            ->assertSee('Reunião Quadrimestral DEPAC')
            ->assertSee('Comparecimento do ministério e colaboradores da regional.');
    }

    public function test_pdf_layout_uses_side_margins_column_gap_and_validated_footer(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(CcbReferenceDataSeeder::class);

        Carbon::setTestNow(Carbon::parse('2026-10-19 16:20:00', 'UTC'));

        $regional = Regional::query()->create([
            'name' => 'Regional Teste',
            'slug' => 'regional-teste',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'regional_id' => $regional->id,
            'is_super_admin' => true,
        ]);
        $batismo = EventType::query()->where('slug', 'batismo')->firstOrFail();

        Event::query()->create([
            'regional_id' => $regional->id,
            'title' => 'Batismo boletim',
            'event_type_id' => $batismo->id,
            'starts_at' => Carbon::parse('2026-10-20 18:30:00'),
            'ends_at' => Carbon::parse('2026-10-20 20:30:00'),
            'status' => 'published',
            'include_on_baptism_list' => true,
            'created_by' => $user->id,
        ]);

        $report = app(BaptismListReportBuilder::class)->generateOrSync(
            $regional->id,
            Carbon::parse('2026-10-15'),
            $user,
        );
        $report->update(['status' => BaptismListReport::STATUS_READY]);

        for ($i = 0; $i < 70; $i++) {
            BaptismListReportItem::query()->create([
                'baptism_list_report_id' => $report->id,
                'section_key' => 'batismos',
                'sort_order' => 100 + $i,
                'is_visible' => true,
                'source_type' => 'event',
                'payload' => [
                    'date' => sprintf('%02d/11', ($i % 28) + 1),
                    'weekday' => 'Sáb',
                    'time' => '18h30',
                    'location_label' => 'Congregação de Teste com nome longo – Cidade '.$i,
                    'attendant' => 'Cooperador '.$i,
                    'title' => 'Batismo '.$i,
                ],
            ]);
        }

        $report->load(['items', 'regional']);

        $sections = $report->items->where('is_visible', true)->groupBy('section_key');
        $html = view('reports.baptism-list.pdf', [
            'report' => $report,
            'sections' => $sections,
        ])->render();

        $this->assertStringContainsString('margin: 10mm 14mm 16mm 14mm;', $html);
        $this->assertStringContainsString('padding-right: 4mm;', $html);
        $this->assertStringContainsString('padding-left: 4mm;', $html);
        $this->assertStringContainsString('col-left', $html);
        $this->assertStringContainsString('col-right', $html);
        $this->assertStringContainsString('Gerado em 19/10/2026 16:20', $html);
        $this->assertStringContainsString('Versão validada R00', $html);
        $this->assertStringNotContainsString('border-spacing:', $html);
        $this->assertGreaterThan(1, substr_count($html, 'class="sheet-page"'));
        $this->assertSame(
            substr_count($html, 'class="sheet-page"') - 1,
            substr_count($html, 'page-break-before: always')
        );

        $pdf = Pdf::loadView('reports.baptism-list.pdf', [
            'report' => $report,
            'sections' => $sections,
        ])->setPaper('a4')->output();

        $this->assertStringStartsWith('%PDF', $pdf);

        Carbon::setTestNow();
    }

    public function test_docx_export_and_libras_icon_follow_the_bulletin(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(CcbReferenceDataSeeder::class);

        $regional = Regional::query()->create([
            'name' => 'Regional Teste',
            'slug' => 'regional-teste',
            'is_active' => true,
        ]);
        $other = Regional::query()->create([
            'name' => 'Outra Regional',
            'slug' => 'outra-regional',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'regional_id' => $regional->id,
            'is_super_admin' => true,
        ]);
        $batismo = EventType::query()->where('slug', 'batismo')->firstOrFail();

        Event::query()->create([
            'regional_id' => $regional->id,
            'title' => 'Batismo com Libras',
            'event_type_id' => $batismo->id,
            'starts_at' => Carbon::parse('2026-10-20 18:30:00'),
            'ends_at' => Carbon::parse('2026-10-20 20:30:00'),
            'status' => 'published',
            'include_on_baptism_list' => true,
            'has_libras' => true,
            'created_by' => $user->id,
        ]);

        $report = app(BaptismListReportBuilder::class)->generateOrSync(
            $regional->id,
            Carbon::parse('2026-10-15'),
            $user,
        );
        $report->update(['status' => BaptismListReport::STATUS_READY]);
        $report->load(['items', 'regional']);

        $this->assertTrue($report->items->contains(fn ($item) => ! empty($item->payload['has_libras'])));

        session(['nexus.active_regional_id' => $regional->id]);
        Livewire::actingAs($user)->test(BaptismListReportPage::class)
            ->assertSee('Baixar PDF')
            ->assertSee('Baixar Word')
            ->assertSee('images/libras.png', false)
            ->assertSee('alt="Libras"', false);

        $sections = $report->items->where('is_visible', true)->groupBy('section_key');
        $html = view('reports.baptism-list.pdf', [
            'report' => $report,
            'sections' => $sections,
        ])->render();
        $this->assertStringContainsString('alt="Libras"', $html);
        $this->assertStringContainsString('data:image/png;base64,', $html);
        $this->assertStringNotContainsString('<em>(Libras)</em>', $html);

        $pdf = $this->actingAs($user)->get(route('secretaria.baptism-list.pdf', $report));
        $pdf->assertOk();
        $this->assertStringStartsWith('%PDF', $pdf->getContent());

        $docx = $this->actingAs($user)->get(route('secretaria.baptism-list.docx', $report));
        $docx->assertOk();
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            (string) $docx->headers->get('content-type')
        );
        $this->assertStringContainsString(
            'lista-batismos-2026-10-15-R00.docx',
            (string) $docx->headers->get('content-disposition')
        );

        $binary = $docx->getContent();
        $this->assertStringStartsWith('PK', $binary);

        $tmp = tempnam(sys_get_temp_dir(), 'docx');
        file_put_contents($tmp, $binary);
        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($tmp) === true);
        $xml = (string) $zip->getFromName('word/document.xml');
        $media = false;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            if (str_starts_with($name, 'word/media/') && str_ends_with(strtolower($name), '.png')) {
                $media = str_starts_with((string) $zip->getFromName($name), "\x89PNG");
            }
        }
        $zip->close();
        @unlink($tmp);

        $this->assertStringContainsString('Congregação Cristã no Brasil', $xml);
        $this->assertStringContainsString('Batismo com Libras', $xml);
        $this->assertStringContainsString('Lista de Batismos e Diversos', $xml);
        $this->assertStringContainsString('w:line="240"', $xml);
        $this->assertStringNotContainsString('w:line="480"', $xml);
        $this->assertSame(1, substr_count($xml, '<w:sectPr'));
        $this->assertStringContainsString('w:num="2"', $xml);
        $this->assertTrue($media);

        $outsider = User::factory()->create(['regional_id' => $other->id]);
        $outsider->givePermissionTo('visualizar_relatorios');
        $this->actingAs($outsider)
            ->get(route('secretaria.baptism-list.docx', $report))
            ->assertForbidden();
    }
}
