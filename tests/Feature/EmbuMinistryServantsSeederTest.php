<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventRoleAssignment;
use App\Models\EventRoleTemplate;
use App\Models\EventType;
use App\Models\Location;
use App\Models\MinistryServant;
use App\Models\PublicPosition;
use App\Models\Regional;
use Database\Seeders\CcbReferenceDataSeeder;
use Database\Seeders\EmbuMinistryServantsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmbuMinistryServantsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_is_idempotent_and_assigns_the_known_attendant_as_anciao(): void
    {
        $this->seed(CcbReferenceDataSeeder::class);

        $regional = Regional::query()->create([
            'name' => 'Regional Embu das Artes',
            'slug' => 'embu-das-artes',
            'is_active' => true,
        ]);
        $names = require database_path('seeders/data/embu_ministry_servants_2026.php');
        $atende = EventRoleTemplate::query()->where('slug', 'atende')->firstOrFail();
        $batismo = EventType::query()->where('slug', 'batismo')->firstOrFail();
        $jovens = EventType::query()->where('slug', 'culto-jovens-12')->firstOrFail();

        $saoLourenco = Location::query()->create([
            'name' => 'São Lourenço da Serra - Central',
            'city' => 'São Lourenço da Serra',
            'regional_id' => $regional->id,
        ]);
        $embu = Location::query()->create([
            'name' => 'Embu das Artes - Central',
            'city' => 'Embu das Artes',
            'regional_id' => $regional->id,
        ]);
        $bras = Location::query()->create([
            'name' => 'Brás',
            'city' => 'São Paulo',
            'regional_id' => $regional->id,
        ]);
        $barnabes = Location::query()->create([
            'name' => 'Barnabés – Juquitiba',
            'city' => 'Juquitiba',
            'regional_id' => $regional->id,
        ]);

        $marked = $this->event($regional, $batismo, $saoLourenco, 'Batismo SLS - Central', '2026-09-06 18:30:00');
        $labeled = $this->event($regional, $batismo, $embu, 'Batismo já rotulado', '2026-02-01 18:30:00');
        $this->attend($labeled, $atende, 'Elias Teixeira da Luz');
        $givenName = $this->event($regional, $batismo, $embu, 'Reunião com prenome', '2026-02-02 18:30:00');
        $this->attend($givenName, $atende, 'Isaias');
        $brasEvent = $this->event($regional, $batismo, $bras, 'Batismo Brás (1º sábado)', '2026-09-05 18:30:00');
        $this->attend($brasEvent, $atende, 'Aparecido Messias');
        $barnabesEvent = $this->event($regional, $jovens, $barnabes, 'Culto Para Jovens - JUQ - Barnabés', '2026-10-17 19:30:00');

        $this->seed(EmbuMinistryServantsSeeder::class);
        $this->seed(EmbuMinistryServantsSeeder::class);

        $this->assertSame(count($names['names']), MinistryServant::query()->where('regional_id', $regional->id)->count());
        $this->assertSame(
            count($names['names']),
            MinistryServant::query()->where('regional_id', $regional->id)->distinct('name_key')->count('name_key'),
        );

        $aparecido = MinistryServant::query()->where('name_key', MinistryServant::nameKey('Aparecido Messias'))->firstOrFail();
        $this->assertSame('Ancião', $aparecido->ministry);
        $this->assertSame('', $aparecido->phone);
        $position = PublicPosition::query()->with('department')->findOrFail($aparecido->public_position_id);
        $this->assertSame('anciao', $position->slug);
        $this->assertSame('ministerio', $position->department?->slug);

        $markedAssignment = EventRoleAssignment::query()->where('event_id', $marked->id)->get();
        $this->assertCount(1, $markedAssignment);
        $this->assertSame('Aparecido Messias', $markedAssignment->first()->assignee_label);
        $this->assertSame($aparecido->id, data_get($markedAssignment->first()->meta, 'ministry_servant_id'));

        $elias = MinistryServant::query()->where('name_key', MinistryServant::nameKey('Elias Teixeira da Luz'))->firstOrFail();
        $labeledAssignment = EventRoleAssignment::query()->where('event_id', $labeled->id)->get();
        $this->assertCount(1, $labeledAssignment);
        $this->assertSame('Elias Teixeira da Luz', $labeledAssignment->first()->assignee_label);
        $this->assertSame($elias->id, data_get($labeledAssignment->first()->meta, 'ministry_servant_id'));
        $this->assertSame('Ancião', $elias->ministry);

        $roberto = MinistryServant::query()->where('name_key', MinistryServant::nameKey('Roberto Carlos Pereira'))->firstOrFail();
        $barnabesAssignment = EventRoleAssignment::query()->where('event_id', $barnabesEvent->id)->get();
        $this->assertCount(1, $barnabesAssignment);
        $this->assertSame('Roberto Carlos Pereira', $barnabesAssignment->first()->assignee_label);
        $this->assertSame($roberto->id, data_get($barnabesAssignment->first()->meta, 'ministry_servant_id'));
        $this->assertSame('Ancião', $roberto->ministry);
        $this->assertNull(MinistryServant::query()->where('name_key', MinistryServant::nameKey('Evandro Wagner'))->first());

        $this->assertNull(MinistryServant::query()->where('name_key', 'isaias')->first());
        $givenAssignment = EventRoleAssignment::query()->where('event_id', $givenName->id)->firstOrFail();
        $this->assertSame('Isaias', $givenAssignment->assignee_label);
        $this->assertNull(data_get($givenAssignment->meta, 'ministry_servant_id'));

        $brasAssignment = EventRoleAssignment::query()->where('event_id', $brasEvent->id)->get();
        $this->assertCount(1, $brasAssignment);
        $this->assertSame('Aparecido Messias', $brasAssignment->first()->assignee_label);
        $this->assertNull(data_get($brasAssignment->first()->meta, 'ministry_servant_id'));
    }

    private function event(Regional $regional, EventType $type, Location $location, string $title, string $startsAt): Event
    {
        return Event::query()->create([
            'regional_id' => $regional->id,
            'location_id' => $location->id,
            'title' => $title,
            'event_type_id' => $type->id,
            'starts_at' => $startsAt,
            'ends_at' => '2026-12-31 20:30:00',
            'status' => 'published',
        ]);
    }

    private function attend(Event $event, EventRoleTemplate $template, string $label): void
    {
        EventRoleAssignment::query()->create([
            'event_id' => $event->id,
            'event_role_template_id' => $template->id,
            'assignee_label' => $label,
        ]);
    }
}
