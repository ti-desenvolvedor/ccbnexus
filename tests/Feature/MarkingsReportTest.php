<?php

namespace Tests\Feature;

use App\Livewire\Secretaria\MarkingsReportPage;
use App\Models\Event;
use App\Models\EventRoleAssignment;
use App\Models\EventType;
use App\Models\Location;
use App\Models\MinistryServant;
use App\Models\PublicDepartment;
use App\Models\PublicPosition;
use App\Models\Regional;
use App\Models\User;
use App\Services\BaptismListReportBuilder;
use App\Services\MarkingsReportBuilder;
use Carbon\Carbon;
use Database\Seeders\CcbReferenceDataSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class MarkingsReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-19 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_default_filters_show_required_marking_events_from_today(): void
    {
        [$user] = $this->seedReport();

        $this->actingAs($user);

        Livewire::test(MarkingsReportPage::class)
            ->assertSet('date_from', '2026-10-19')
            ->assertSet('date_to', '2027-01-31')
            ->assertSet('event_type_slugs', [
                'batismo',
                'santa-ceia',
                'culto-jovens-12',
                'reuniao-mocidade',
                'ensaio-regional',
            ])
            ->assertSee('Batismo de hoje')
            ->assertSee('Santa Ceia de novembro')
            ->assertSee('Culto jovens de novembro')
            ->assertSee('Reunião da mocidade')
            ->assertSee('Ensaio regional de dezembro')
            ->assertDontSee('Batismo passado')
            ->assertDontSee('Reunião fora da lista')
            ->assertDontSee('Batismo longe');
    }

    public function test_date_filter_cannot_start_before_today_and_limits_the_range(): void
    {
        [$user] = $this->seedReport();

        $this->actingAs($user);

        Livewire::test(MarkingsReportPage::class)
            ->set('date_from', '2026-10-01')
            ->assertSet('date_from', '2026-10-19')
            ->set('date_to', '2026-10-19')
            ->assertSee('Batismo de hoje')
            ->assertDontSee('Santa Ceia de novembro')
            ->assertDontSee('Batismo passado');
    }

    public function test_event_type_filter_hides_unchecked_types(): void
    {
        [$user] = $this->seedReport();

        $this->actingAs($user);

        Livewire::test(MarkingsReportPage::class)
            ->set('event_type_slugs', ['ensaio-regional'])
            ->assertSee('Ensaio regional de dezembro')
            ->assertDontSee('Batismo de hoje')
            ->assertDontSee('Santa Ceia de novembro')
            ->assertDontSee('Culto jovens de novembro')
            ->assertDontSee('Reunião da mocidade');
    }

    /**
     * @return array{0: User}
     */
    protected function seedReport(): array
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

        $this->event($regional, $user, 'batismo', 'Batismo passado', '2026-10-18 19:00:00');
        $this->event($regional, $user, 'batismo', 'Batismo de hoje', '2026-10-19 19:00:00');
        $this->event($regional, $user, 'santa-ceia', 'Santa Ceia de novembro', '2026-11-01 19:00:00');
        $this->event($regional, $user, 'culto-jovens-12', 'Culto jovens de novembro', '2026-11-15 19:00:00');
        $this->event($regional, $user, 'reuniao-mocidade', 'Reunião da mocidade', '2026-11-20 19:00:00');
        $this->event($regional, $user, 'ensaio-regional', 'Ensaio regional de dezembro', '2026-12-05 19:00:00');
        $this->event($regional, $user, 'reuniao', 'Reunião fora da lista', '2026-11-02 19:00:00', true);
        $this->event($regional, $user, 'batismo', 'Batismo longe', '2027-04-01 19:00:00');

        return [$user, $regional];
    }

    public function test_other_event_select_includes_types_outside_the_default_list(): void
    {
        [$user, $regional] = $this->seedReport();
        $this->event($regional, $user, 'reuniao', 'Reunião avulsa', '2026-11-03 19:00:00', false);

        $this->actingAs($user);

        Livewire::test(MarkingsReportPage::class)
            ->set('other_slug', 'reuniao')
            ->assertSet('extra_slugs', ['reuniao'])
            ->assertSee('Reunião avulsa')
            ->assertSee('Reunião fora da lista');
    }

    public function test_bras_location_events_are_absent_and_do_not_count_as_missing(): void
    {
        [$user, $regional] = $this->seedReport();
        $bras = Location::query()->create([
            'name' => 'Brás',
            'city' => 'São Paulo',
            'regional_id' => $regional->id,
        ]);
        $brasPlain = Location::query()->create([
            'name' => 'Bras Sem Acento',
            'city' => 'São Paulo',
            'regional_id' => $regional->id,
        ]);
        $embu = Location::query()->create([
            'name' => 'Embu das Artes - Central',
            'city' => 'Embu das Artes',
            'regional_id' => $regional->id,
        ]);

        $brasEvent = $this->event($regional, $user, 'batismo', 'Batismo no Brás', '2026-11-08 19:00:00', true, $bras->id);
        $plainEvent = $this->event($regional, $user, 'batismo', 'Batismo no Bras', '2026-11-09 19:00:00', true, $brasPlain->id);
        $embuEvent = $this->event($regional, $user, 'batismo', 'Batismo em Embu', '2026-11-10 19:00:00', true, $embu->id);

        $bulletin = app(BaptismListReportBuilder::class)->generateOrSync(
            $regional->id,
            Carbon::parse('2026-10-19'),
            $user,
        );
        $bulletinIds = $bulletin->items->where('source_type', 'event')->pluck('source_id');
        $this->assertTrue($bulletinIds->contains($brasEvent->id));
        $this->assertTrue($bulletinIds->contains($plainEvent->id));
        $this->assertTrue($bulletin->items->contains(
            fn ($item) => ($item->payload['location_label'] ?? null) === 'Brás – São Paulo' && $item->is_visible
        ));

        $this->actingAs($user);

        Livewire::test(MarkingsReportPage::class)
            ->assertViewHas('missingCount', 6)
            ->assertViewHas('groups', function (array $groups) use ($brasEvent, $plainEvent, $embuEvent) {
                $ids = collect($groups)->flatMap(fn (array $group) => $group['rows'])->pluck('event_id');

                return ! $ids->contains($brasEvent->id)
                    && ! $ids->contains($plainEvent->id)
                    && $ids->contains($embuEvent->id);
            })
            ->assertDontSee('Brás')
            ->assertDontSee('Bras Sem Acento')
            ->assertSee('Embu das Artes - Central')
            ->set('filter', 'missing')
            ->assertViewHas('missingCount', 6)
            ->assertDontSee('Brás')
            ->assertDontSee('Bras Sem Acento')
            ->assertSee('Embu das Artes - Central');
    }

    public function test_report_is_grouped_by_event_type_then_date(): void
    {
        [$user, $regional] = $this->seedReport();
        $this->event($regional, $user, 'batismo', 'Batismo de dezembro tardio', '2026-12-20 19:00:00');
        $this->event($regional, $user, 'santa-ceia', 'Santa Ceia de outubro', '2026-10-20 19:00:00');

        $this->actingAs($user);

        Livewire::test(MarkingsReportPage::class)
            ->assertSeeInOrder(['Batismo de dezembro tardio', 'Santa Ceia de outubro']);
    }

    public function test_servant_registry_rejects_duplicate_name_and_phone(): void
    {
        [$user, $regional] = $this->seedReport();
        $office = $this->ministryOffice($regional);
        $event = Event::query()->where('title', 'Batismo de hoje')->firstOrFail();
        $this->actingAs($user);

        Livewire::test(MarkingsReportPage::class)
            ->call('openAttendant', $event->id)
            ->set('servant_name', 'José da Silva')
            ->set('servant_position_id', (string) $office->id)
            ->set('servant_phone', '(11) 98765-4321')
            ->call('saveAndAssign')
            ->assertHasNoErrors()
            ->assertSee('José da Silva')
            ->assertSee('Ancião')
            ->call('openAttendant', $event->id)
            ->set('servant_name', 'Jose da Silva')
            ->set('servant_position_id', (string) $office->id)
            ->set('servant_phone', '11988887777')
            ->call('saveAndAssign')
            ->assertHasErrors(['servant_name'])
            ->set('servant_name', 'Ana Pereira')
            ->set('servant_phone', '11 98765-4321')
            ->call('saveAndAssign')
            ->assertHasErrors(['servant_phone']);

        $this->assertSame(1, MinistryServant::query()->count());
        $this->assertSame($office->id, MinistryServant::query()->value('public_position_id'));
    }

    public function test_servant_can_be_saved_without_an_event_or_phone_and_then_selected(): void
    {
        [$user, $regional] = $this->seedReport();
        $office = $this->ministryOffice($regional);
        $event = Event::query()->where('title', 'Batismo de hoje')->firstOrFail();
        $this->actingAs($user);

        $component = Livewire::test(MarkingsReportPage::class)
            ->call('openRegistry')
            ->set('servant_name', 'Maria dos Santos')
            ->set('servant_position_id', (string) $office->id)
            ->set('servant_phone', '')
            ->call('saveServant')
            ->assertHasNoErrors()
            ->assertSee('Maria dos Santos');

        $servant = MinistryServant::query()->where('full_name', 'Maria dos Santos')->first();
        $this->assertNotNull($servant);
        $this->assertSame('', $servant->phone);
        $this->assertSame(0, EventRoleAssignment::query()->where('event_id', $event->id)->count());

        $component
            ->call('openAttendant', $event->id)
            ->set('existing_servant_id', (string) $servant->id)
            ->call('useExistingServant')
            ->assertHasNoErrors();

        $assignment = EventRoleAssignment::query()->where('event_id', $event->id)->first();
        $this->assertSame('Maria dos Santos', $assignment?->assignee_label);
    }

    public function test_assigning_a_servant_marks_the_event(): void
    {
        [$user, $regional] = $this->seedReport();
        $this->actingAs($user);
        $office = $this->ministryOffice($regional, 'Diácono', 'diacono');
        $servant = MinistryServant::register($regional->id, 'Paulo Mendes', $office->id, '11912345678');
        $event = Event::query()->where('title', 'Batismo de hoje')->firstOrFail();

        Livewire::test(MarkingsReportPage::class)
            ->call('assignServant', $event->id, (string) $servant->id)
            ->assertSee('Paulo Mendes')
            ->assertSee('Diácono')
            ->assertDontSee('(11) 91234-5678');

        $assignment = EventRoleAssignment::query()->where('event_id', $event->id)->first();
        $this->assertSame('Paulo Mendes', $assignment?->assignee_label);
        $this->assertSame($servant->id, data_get($assignment?->meta, 'ministry_servant_id'));
    }

    public function test_pdf_leaves_a_line_when_nobody_is_marked_and_excel_downloads(): void
    {
        [$user, $regional] = $this->seedReport();
        $this->actingAs($user);
        $servant = MinistryServant::register($regional->id, 'Paulo Mendes', $this->ministryOffice($regional)->id, '11912345678');
        $event = Event::query()->where('title', 'Batismo de hoje')->firstOrFail();
        $servant->assignTo($event);

        $query = [
            'date_from' => '2026-10-19',
            'date_to' => '2027-01-31',
            'filter' => 'all',
            'slugs' => 'batismo,santa-ceia,culto-jovens-12,reuniao-mocidade,ensaio-regional',
        ];

        $pdf = $this->get(route('secretaria.markings.export', ['format' => 'pdf'] + $query));
        $pdf->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $pdf->headers->get('content-type'));

        $html = view('reports.markings.pdf', [
            'groups' => [[
                'slug' => 'batismo',
                'name' => 'Batismo',
                'rows' => [[
                    'type_name' => 'Batismo',
                    'when' => '19/10/2026 19:00',
                    'place' => 'Casa sem nome',
                    'attendant' => '',
                    'ministry' => '',
                    'phone' => '',
                ]],
            ]],
            'from' => Carbon::parse('2026-10-19'),
            'to' => Carbon::parse('2027-01-31'),
            'regionalName' => 'Regional Teste',
        ])->render();
        $this->assertStringContainsString('<h2>Batismo</h2>', $html);
        $this->assertStringContainsString('marking-write-line', $html);
        $this->assertStringContainsString('>Quem atende<', $html);
        $this->assertStringNotContainsString('Ministério', $html);
        $this->assertStringNotContainsString('Tipo de evento', $html);
        $this->assertStringContainsString('Casa sem nome', $html);
        $this->assertStringContainsString('Relatório de Marcações', $html);
        $this->assertMatchesRegularExpression(
            '/<th>Data<\/th>\s*<th>Local<\/th>\s*<th>Quem atende<\/th>.*<td class="when">19\/10\/2026 19:00<\/td>\s*<td class="place">Casa sem nome<\/td>\s*<td class="who"><span class="marking-write-line">/s',
            $html,
        );

        $excel = $this->get(route('secretaria.markings.export', ['format' => 'xlsx'] + $query));
        $excel->assertOk();
        $tmp = tempnam(sys_get_temp_dir(), 'marcacoes');
        file_put_contents($tmp, $excel->streamedContent());
        $rows = IOFactory::load($tmp)->getActiveSheet()->toArray();
        @unlink($tmp);
        $marked = collect($rows)->first(fn ($row) => ($row[2] ?? null) === 'Batismo de hoje');
        $this->assertNotNull($marked);
        $this->assertSame('Paulo Mendes', $marked[3]);
        $open = collect($rows)->first(fn ($row) => ($row[2] ?? null) === 'Santa Ceia de novembro');
        $this->assertNotNull($open);
        $this->assertTrue($open[3] === null || $open[3] === '');
    }

    public function test_pdf_lists_attendant_names_grouped_by_type_then_date_then_location(): void
    {
        [$user, $regional] = $this->seedReport();
        $this->actingAs($user);

        $zulu = Location::query()->create([
            'name' => 'Zulu Local',
            'city' => 'Embu',
            'regional_id' => $regional->id,
        ]);
        $alfa = Location::query()->create([
            'name' => 'Alfa Local',
            'city' => 'Embu',
            'regional_id' => $regional->id,
        ]);
        $meio = Location::query()->create([
            'name' => 'Meio Local',
            'city' => 'Embu',
            'regional_id' => $regional->id,
        ]);

        $this->event($regional, $user, 'batismo', 'Batismo Zulu', '2026-11-02 19:00:00', true, $zulu->id);
        $alfaEvent = $this->event($regional, $user, 'batismo', 'Batismo Alfa', '2026-11-02 19:00:00', true, $alfa->id);
        $this->event($regional, $user, 'batismo', 'Batismo depois', '2026-11-03 10:00:00', true, $meio->id);
        $this->event($regional, $user, 'santa-ceia', 'Santa Ceia cedo', '2026-10-21 19:00:00', true, $alfa->id);

        $servant = MinistryServant::register($regional->id, 'Paulo Mendes', $this->ministryOffice($regional)->id, '11912345678');
        $servant->assignTo($alfaEvent);

        $builder = app(MarkingsReportBuilder::class);
        [$from, $to] = $builder->period('2026-10-19', '2027-01-31');
        $report = $builder->report(
            $user,
            $regional->id,
            array_keys(MarkingsReportBuilder::DEFAULT_TYPES),
            [],
            $from,
            $to,
            'all',
        );

        $html = view('reports.markings.pdf', [
            'groups' => $report['groups'],
            'from' => $from,
            'to' => $to,
            'regionalName' => 'Regional Teste',
        ])->render();

        $batismo = strpos($html, '<h2>Batismo</h2>');
        $alfaPos = strpos($html, 'Alfa Local');
        $zuluPos = strpos($html, 'Zulu Local');
        $meioPos = strpos($html, 'Meio Local');
        $ceia = strpos($html, '<h2>Santa Ceia</h2>');

        $this->assertNotFalse($batismo);
        $this->assertNotFalse($ceia);
        $this->assertTrue($batismo < $alfaPos && $alfaPos < $zuluPos && $zuluPos < $meioPos && $meioPos < $ceia);
        $this->assertMatchesRegularExpression(
            '/Alfa Local<\/td>\s*<td class="who">Paulo Mendes<\/td>.*Zulu Local<\/td>\s*<td class="who"><span class="marking-write-line">/s',
            $html,
        );
        $this->assertDoesNotMatchRegularExpression('/<td>Batismo<\/td>/', $html);
    }

    protected function ministryOffice(Regional $regional, string $name = 'Ancião', string $slug = 'anciao'): PublicPosition
    {
        $department = PublicDepartment::query()->firstOrCreate(
            ['scope' => PublicDepartment::SCOPE_REGIONAL, 'regional_id' => $regional->id, 'slug' => 'ministerio'],
            ['name' => 'Ministério', 'is_active' => true, 'sort_order' => 10],
        );

        return PublicPosition::query()->firstOrCreate(
            ['public_department_id' => $department->id, 'slug' => $slug],
            ['name' => $name, 'is_active' => true, 'sort_order' => 10],
        );
    }

    protected function event(
        Regional $regional,
        User $user,
        string $slug,
        string $title,
        string $startsAt,
        bool $onList = true,
        ?int $locationId = null,
    ): Event {
        return Event::query()->create([
            'regional_id' => $regional->id,
            'location_id' => $locationId,
            'title' => $title,
            'event_type_id' => EventType::query()->where('slug', $slug)->firstOrFail()->id,
            'starts_at' => Carbon::parse($startsAt),
            'ends_at' => Carbon::parse($startsAt)->addHours(2),
            'status' => 'published',
            'include_on_baptism_list' => $onList,
            'created_by' => $user->id,
        ]);
    }
}
