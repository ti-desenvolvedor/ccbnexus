<?php

namespace Tests\Feature;

use App\Exceptions\Agenda\AgendaRegionalContextRequired;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Regional;
use App\Models\User;
use App\Services\Agenda\AgendaMonthLayout;
use App\Services\Agenda\AgendaReadCriteria;
use App\Services\Agenda\AgendaReadQuery;
use App\Services\Agenda\AgendaReadResult;
use App\Services\Agenda\AgendaYearBook;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgendaReadQueryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_unscoped_events_stay_hidden_without_active_context(): void
    {
        $this->regionals();
        $user = User::factory()->create();
        $user->givePermissionTo('visualizar_evento');
        $this->event(null, 'Sem regional');
        session()->forget('nexus.active_regional_id');

        $titles = $this->pluck(app(AgendaReadQuery::class)->read($user, new AgendaReadCriteria(
            mode: AgendaReadCriteria::MODE_OCCURRENCES,
            window: AgendaReadCriteria::WINDOW_MONTH,
            cursor: '2026-10-16',
        )));

        $this->assertSame([], $titles);
    }

    public function test_hides_other_regional_and_null_regional_when_context_is_set(): void
    {
        [$a, $b] = $this->regionals();
        $user = $this->viewer($a);
        $this->event($a, 'Visivel A');
        $this->event($b, 'Outra regional');
        $this->event(null, 'Sem regional');

        $titles = $this->titles($user, $a->id);

        $this->assertSame(['Visivel A'], $titles);
    }

    public function test_cancelled_rejected_and_stale_child_of_cancelled_parent_stay_out(): void
    {
        [$a] = $this->regionals();
        $user = $this->viewer($a);
        $this->event($a, 'Cancelado', ['status' => 'cancelled']);
        $this->event($a, 'Rejeitado', ['status' => 'rejected']);
        $parent = $this->event($a, 'Serie cancelada', [
            'status' => 'cancelled',
            'starts_at' => '2026-09-01 19:00:00',
            'ends_at' => '2026-09-01 21:00:00',
        ]);
        $this->event($a, 'Filho ainda publicado', [
            'status' => 'published',
            'is_occurrence' => true,
            'parent_event_id' => $parent->id,
        ]);
        $published = $this->event($a, 'Serie viva');
        $this->event($a, 'Ocorrencia cancelada', [
            'status' => 'cancelled',
            'is_occurrence' => true,
            'parent_event_id' => $published->id,
            'starts_at' => '2026-10-18 19:00:00',
            'ends_at' => '2026-10-18 21:00:00',
        ]);

        $this->assertSame(['Serie viva'], $this->titles($user, $a->id));
    }

    public function test_draft_is_visible_only_to_editors_and_super_admin(): void
    {
        [$a] = $this->regionals();
        $this->event($a, 'Rascunho');
        $this->event($a, 'Publicado', ['status' => 'draft']);
        Event::query()->where('title', 'Rascunho')->update(['status' => 'draft']);
        $this->event($a, 'No ar', ['status' => 'published']);

        $viewer = $this->titles($this->viewer($a), $a->id);
        $editor = $this->titles($this->editor($a), $a->id);
        $admin = $this->titles($this->superAdmin(), $a->id);

        $this->assertSame(['No ar'], $viewer);
        $this->assertEqualsCanonicalizing(['Rascunho', 'Publicado', 'No ar'], $editor);
        $this->assertEqualsCanonicalizing(['Rascunho', 'Publicado', 'No ar'], $admin);
    }

    public function test_published_parent_keeps_draft_child_in_the_child_window(): void
    {
        [$a] = $this->regionals();
        $parent = $this->event($a, 'Pai publicado', [
            'status' => 'published',
            'starts_at' => '2026-09-10 19:00:00',
            'ends_at' => '2026-09-10 21:00:00',
        ]);
        $this->event($a, 'Filho em outubro', [
            'status' => 'draft',
            'is_occurrence' => true,
            'parent_event_id' => $parent->id,
            'starts_at' => '2026-10-20 19:00:00',
            'ends_at' => '2026-10-20 21:00:00',
        ]);

        $this->assertSame(['Filho em outubro'], $this->titles($this->viewer($a), $a->id));
    }

    public function test_week_and_month_use_half_open_overlap(): void
    {
        [$a] = $this->regionals();
        $user = $this->viewer($a);
        $this->event($a, 'Entra na semana', [
            'starts_at' => '2026-10-11 22:00:00',
            'ends_at' => '2026-10-12 01:00:00',
        ]);
        $this->event($a, 'Fora da semana', [
            'starts_at' => '2026-10-19 00:00:00',
            'ends_at' => '2026-10-19 02:00:00',
        ]);
        $this->event($a, 'Entra no mes', [
            'starts_at' => '2026-09-30 22:00:00',
            'ends_at' => '2026-10-01 02:00:00',
        ]);
        $this->event($a, 'Fora do mes', [
            'starts_at' => '2026-11-01 00:00:00',
            'ends_at' => '2026-11-01 02:00:00',
        ]);

        $week = $this->titles($user, $a->id, window: AgendaReadCriteria::WINDOW_WEEK);
        $month = $this->titles($user, $a->id);

        $this->assertSame(['Entra na semana'], $week);
        $this->assertEqualsCanonicalizing(['Entra na semana', 'Entra no mes', 'Fora da semana'], $month);
        $this->assertNotContains('Fora do mes', $month);
    }

    public function test_pdf_without_a_single_regional_throws(): void
    {
        $this->regionals();
        $user = $this->superAdmin();
        session()->forget('nexus.active_regional_id');

        $this->expectException(AgendaRegionalContextRequired::class);

        app(AgendaReadQuery::class)->read($user, AgendaReadCriteria::pdf('2026-10-16'));
    }

    public function test_shared_filters_match_in_both_modes(): void
    {
        [$a] = $this->regionals();
        $user = $this->viewer($a);
        $bulletin = EventType::query()->create(['name' => 'Batismo', 'slug' => 'batismo', 'is_active' => true, 'sort_order' => 1]);
        $other = EventType::query()->create(['name' => 'Reunião', 'slug' => 'reuniao', 'is_active' => true, 'sort_order' => 2]);
        $this->event($a, 'Ensaio boletim', [
            'event_type_id' => $bulletin->id,
            'include_on_baptism_list' => true,
            'whatsapp_enabled' => false,
        ]);
        $this->event($a, 'Ensaio whats', [
            'event_type_id' => $other->id,
            'include_on_baptism_list' => false,
            'whatsapp_enabled' => true,
        ]);
        $this->event($a, 'Serie antiga', [
            'starts_at' => '2025-01-10 19:00:00',
            'ends_at' => '2025-01-10 21:00:00',
            'include_on_baptism_list' => true,
        ]);

        $criteria = new AgendaReadCriteria(
            mode: AgendaReadCriteria::MODE_OCCURRENCES,
            window: AgendaReadCriteria::WINDOW_MONTH,
            cursor: '2026-10-16',
            search: 'Ensaio',
            eventTypeId: $bulletin->id,
            channel: 'bulletin_only',
        );
        $series = new AgendaReadCriteria(
            mode: AgendaReadCriteria::MODE_SERIES,
            search: 'Ensaio',
            eventTypeId: $bulletin->id,
            channel: 'bulletin_only',
        );

        session(['nexus.active_regional_id' => $a->id]);
        $query = app(AgendaReadQuery::class);

        $this->assertSame(['Ensaio boletim'], $this->pluck($query->read($user, $criteria)));
        $this->assertSame(['Ensaio boletim'], $this->pluck($query->read($user, $series)));
        $this->assertSame(['Ensaio boletim', 'Serie antiga'], $this->pluck($query->read($user, new AgendaReadCriteria(
            mode: AgendaReadCriteria::MODE_SERIES,
            channel: 'bulletin_only',
        ))));
    }

    public function test_occurrence_window_truncates_at_five_hundred(): void
    {
        [$a] = $this->regionals();
        $user = $this->viewer($a);
        for ($i = 0; $i < 501; $i++) {
            $this->event($a, 'Lote '.$i, [
                'starts_at' => Carbon::parse('2026-10-01 08:00:00')->addMinutes($i),
                'ends_at' => Carbon::parse('2026-10-01 08:00:00')->addMinutes($i + 30),
            ]);
        }

        session(['nexus.active_regional_id' => $a->id]);
        $result = app(AgendaReadQuery::class)->read($user, new AgendaReadCriteria(
            mode: AgendaReadCriteria::MODE_OCCURRENCES,
            window: AgendaReadCriteria::WINDOW_MONTH,
            cursor: '2026-10-16',
        ));

        $this->assertTrue($result->truncated);
        $this->assertCount(500, $result->entries());
        $this->assertSame('Lote 0', $result->entries()->first()->title);
    }

    public function test_refuses_user_without_view_permission(): void
    {
        [$a] = $this->regionals();
        $user = User::factory()->create(['regional_id' => $a->id]);

        $this->expectException(AuthorizationException::class);

        app(AgendaReadQuery::class)->read($user, new AgendaReadCriteria(
            mode: AgendaReadCriteria::MODE_SERIES,
        ));
    }

    public function test_month_grid_keeps_three_chips_and_pdf_routes(): void
    {
        [$a] = $this->regionals();
        $user = $this->editor($a);
        foreach (['Um', 'Dois', 'Tres', 'Quatro'] as $index => $title) {
            $this->event($a, $title, [
                'starts_at' => '2026-10-20 '.(10 + $index).':00:00',
                'ends_at' => '2026-10-20 '.(11 + $index).':00:00',
                'status' => 'published',
            ]);
        }

        session(['nexus.active_regional_id' => $a->id]);
        $result = app(AgendaReadQuery::class)->read($user, AgendaReadCriteria::pdf('2026-10-20'));
        $weeks = app(AgendaMonthLayout::class)->grid($result);
        $day = collect($weeks)->flatten(1)->firstWhere('date', '2026-10-20');

        $this->assertNotNull($day);
        $this->assertCount(3, $day['events']);
        $this->assertSame(1, $day['more']);

        $response = $this->actingAs($user)
            ->get(route('agenda.events.pdf', ['cursor' => '2026-10-20', 'layout' => 'agenda']));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString(
            'agenda-'.$a->slug.'-2026.pdf',
            (string) $response->headers->get('content-disposition'),
        );
    }

    public function test_year_book_has_cover_index_listing_and_every_month(): void
    {
        [$a] = $this->regionals();
        $user = $this->editor($a);
        $this->event($a, 'Culto de outubro', [
            'starts_at' => '2026-10-17 09:00:00',
            'ends_at' => '2026-10-17 11:00:00',
        ]);
        $this->event($a, 'Reunião de março', [
            'starts_at' => '2026-03-04 19:30:00',
            'ends_at' => '2026-03-04 21:00:00',
        ]);

        session(['nexus.active_regional_id' => $a->id]);
        $document = app(AgendaYearBook::class)->compose($user, 2026);

        $this->assertSame(2026, $document['year']);
        $this->assertSame(2, $document['eventCount']);
        $this->assertSame('cover', $document['pages'][0]['kind']);
        $this->assertSame('index', $document['pages'][1]['kind']);
        $this->assertSame('Listagem geral', $document['pages'][1]['entries'][0]['label']);
        $this->assertSame('Janeiro', $document['pages'][1]['entries'][1]['label']);
        $this->assertSame('Dezembro', $document['pages'][1]['entries'][12]['label']);
        $this->assertSame('Anotações', $document['pages'][1]['entries'][13]['label']);

        $kinds = collect($document['pages'])->pluck('kind');
        $this->assertSame(['cover', 'index', 'listing'], $kinds->take(3)->all());
        $this->assertSame(['notes', 'end-cover'], $kinds->slice(-2)->values()->all());
        $this->assertSame(
            $document['pages'][1]['entries'][13]['page'],
            collect($document['pages'])->firstWhere('kind', 'notes')['number'],
        );

        $months = collect($document['pages'])->where('kind', 'month')->pluck('name')->unique()->values()->all();
        $this->assertSame(array_values(AgendaYearBook::MONTHS), $months);
        $firstMonth = $kinds->search('month');
        $notesAt = $kinds->search('notes');
        $this->assertTrue($firstMonth > $kinds->search('listing') && $notesAt > $firstMonth);

        $listing = collect($document['pages'])->firstWhere('kind', 'listing');
        $events = collect($listing['rows'])->where('kind', 'event')->values();
        $this->assertSame(['Reunião de março', 'Culto de outubro'], $events->pluck('title')->all());
        $this->assertSame(['Mar', 'Out'], $events->pluck('months')->all());
        $this->assertSame('Reuniões', collect($listing['rows'])->firstWhere('kind', 'band')['label']);

        $october = collect($document['pages'])->first(fn ($page) => $page['kind'] === 'month' && $page['name'] === 'Outubro' && ! $page['continued']);
        $this->assertFalse($october['empty']);
        $this->assertNotEmpty($october['calendar']);

        $html = view('reports.agenda.year-book', $document)->render();
        $this->assertStringContainsString('Anotações', $html);
        $this->assertStringContainsString('Fim', $html);
        $this->assertStringContainsString('Cada evento aparece uma vez', $html);
        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');
        $pdf->render();
        $this->assertSame(count($document['pages']), $pdf->getDomPDF()->getCanvas()->get_page_count());
    }

    public function test_year_book_listing_groups_a_series_and_merges_months(): void
    {
        [$a] = $this->regionals();
        $user = $this->editor($a);
        $type = EventType::query()->create([
            'name' => 'Batismo',
            'slug' => 'batismo',
            'is_active' => true,
            'sort_order' => 10,
        ]);
        $this->event($a, 'Batismo EA - Central', [
            'event_type_id' => $type->id,
            'starts_at' => '2026-01-24 18:30:00',
            'ends_at' => '2026-01-24 20:30:00',
            'meta' => ['excel_key' => 'batismo-ea-central'],
        ]);
        $this->event($a, 'Batismo EA - Central', [
            'event_type_id' => $type->id,
            'starts_at' => '2026-03-28 18:30:00',
            'ends_at' => '2026-03-28 20:30:00',
            'meta' => ['excel_key' => 'batismo-ea-central'],
        ]);
        $this->event($a, 'Batismo EG - Central (2º sábado)', [
            'event_type_id' => $type->id,
            'starts_at' => '2026-05-09 18:30:00',
            'ends_at' => '2026-05-09 20:30:00',
            'meta' => ['excel_key' => 'batismo-eg-central'],
        ]);

        session(['nexus.active_regional_id' => $a->id]);
        $document = app(AgendaYearBook::class)->compose($user, 2026);
        $listing = collect($document['pages'])->firstWhere('kind', 'listing');
        $this->assertSame(['Batismos'], collect($listing['rows'])->where('kind', 'band')->pluck('label')->all());

        $events = collect($listing['rows'])->where('kind', 'event')->values();
        $this->assertSame(['Batismo EA - Central', 'Batismo EG - Central'], $events->pluck('title')->all());
        $this->assertSame('Jan, Mar', $events[0]['months']);
        $this->assertSame('Mai', $events[1]['months']);
        $this->assertSame(3, $document['eventCount']);
        $this->assertSame('2 compromissos', $document['pages'][1]['entries'][0]['detail']);
    }

    public function test_pdf_http_without_regional_is_a_short_page(): void
    {
        $this->regionals();
        $user = $this->superAdmin();

        $this->actingAs($user)
            ->get(route('agenda.events.pdf', ['cursor' => '2026-10-16']))
            ->assertStatus(422)
            ->assertSee('Selecione uma regional no topo e volte a gerar o PDF.');
    }

    /**
     * @return array{0: Regional, 1: Regional}
     */
    protected function regionals(): array
    {
        return [
            Regional::query()->create(['name' => 'Regional A', 'slug' => 'regional-a', 'is_active' => true]),
            Regional::query()->create(['name' => 'Regional B', 'slug' => 'regional-b', 'is_active' => true]),
        ];
    }

    protected function viewer(Regional $regional): User
    {
        $user = User::factory()->create(['regional_id' => $regional->id]);
        $user->givePermissionTo('visualizar_evento');

        return $user;
    }

    protected function editor(Regional $regional): User
    {
        $user = User::factory()->create(['regional_id' => $regional->id]);
        $user->givePermissionTo(['visualizar_evento', 'editar_evento']);

        return $user;
    }

    protected function superAdmin(): User
    {
        return User::factory()->create(['is_super_admin' => true]);
    }

    protected function event(?Regional $regional, string $title, array $overrides = []): Event
    {
        return Event::query()->create(array_merge([
            'regional_id' => $regional?->id,
            'title' => $title,
            'starts_at' => '2026-10-16 19:00:00',
            'ends_at' => '2026-10-16 21:00:00',
            'status' => 'published',
            'is_occurrence' => false,
        ], $overrides));
    }

    /**
     * @return list<string>
     */
    protected function titles(User $user, int $regionalId, string $window = AgendaReadCriteria::WINDOW_MONTH): array
    {
        session(['nexus.active_regional_id' => $regionalId]);

        return $this->pluck(app(AgendaReadQuery::class)->read($user, new AgendaReadCriteria(
            mode: AgendaReadCriteria::MODE_OCCURRENCES,
            window: $window,
            cursor: '2026-10-16',
        )));
    }

    /**
     * @return list<string>
     */
    protected function pluck(AgendaReadResult $result): array
    {
        return $result->entries()->pluck('title')->all();
    }
}
