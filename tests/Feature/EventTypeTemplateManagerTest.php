<?php

namespace Tests\Feature;

use App\Livewire\Agenda\EventTypeTemplateManager;
use App\Models\Event;
use App\Models\EventType;
use App\Models\EventTypeAudiencePreset;
use App\Models\Location;
use App\Models\Regional;
use App\Models\User;
use App\Services\EventTypeAudiencePresetService;
use Carbon\Carbon;
use Database\Seeders\CcbReferenceDataSeeder;
use Database\Seeders\EventTypeAudiencePresetSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EventTypeTemplateManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_saves_the_template_of_the_event_type(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(CcbReferenceDataSeeder::class);
        $this->seed(EventTypeAudiencePresetSeeder::class);

        $user = User::factory()->create(['is_super_admin' => true]);
        $rrm = EventType::query()->where('slug', 'rrm')->firstOrFail();

        Livewire::actingAs($user)
            ->test(EventTypeTemplateManager::class)
            ->call('edit', $rrm->id)
            ->set('paragraph', 'No dia {day_month}, {weekday}, reunião de teste em {location}.')
            ->call('save')
            ->assertHasNoErrors();

        $saved = EventTypeAudiencePreset::query()->where('event_type_id', $rrm->id)->first();
        $this->assertNotNull($saved);
        $this->assertSame('No dia {day_month}, {weekday}, reunião de teste em {location}.', $saved->invitation_paragraph);
        $this->assertSame('narrative', $saved->report_layout);
    }

    public function test_manager_keeps_formatting_and_strips_unsafe_html(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(CcbReferenceDataSeeder::class);
        $this->seed(EventTypeAudiencePresetSeeder::class);

        $user = User::factory()->create(['is_super_admin' => true]);
        $rrm = EventType::query()->where('slug', 'rrm')->firstOrFail();

        Livewire::actingAs($user)
            ->test(EventTypeTemplateManager::class)
            ->call('edit', $rrm->id)
            ->assertSee('Inserir campo')
            ->assertSee('Negrito')
            ->set('paragraph', '<b onclick="alert(1)">No dia {day_month}</b><script>bad()</script><ul><li>Local {location}</li></ul>')
            ->call('save')
            ->assertHasNoErrors();

        $saved = EventTypeAudiencePreset::query()->where('event_type_id', $rrm->id)->firstOrFail();
        $this->assertStringContainsString('<b>No dia {day_month}</b>', $saved->invitation_paragraph);
        $this->assertStringContainsString('<ul><li>Local {location}</li></ul>', $saved->invitation_paragraph);
        $this->assertStringNotContainsString('<script', $saved->invitation_paragraph);
        $this->assertStringNotContainsString('onclick', $saved->invitation_paragraph);
    }

    public function test_template_fields_are_filled_from_the_event(): void
    {
        $presets = app(EventTypeAudiencePresetService::class);
        $tokens = collect($presets->placeholderCatalog())->pluck('token')->implode(' ');
        $preset = new EventTypeAudiencePreset([
            'invitation_paragraph' => '<b>'.$tokens.'</b>',
        ]);
        $event = new Event([
            'title' => 'Culto de exemplo',
            'starts_at' => Carbon::parse('2026-11-08 19:30:00'),
            'ends_at' => Carbon::parse('2026-11-08 21:00:00'),
        ]);
        $event->setRelation('regional', new Regional(['name' => 'Regional Embu das Artes']));
        $event->setRelation('location', new Location(['name' => 'Casa Central']));

        $rendered = $presets->renderParagraph($preset, $event);

        $this->assertStringNotContainsString('{', $rendered);
        $this->assertStringContainsString('<b>', $rendered);
        $this->assertStringContainsString('Casa Central', $rendered);
        $this->assertStringContainsString('08/11', $rendered);
        $this->assertStringContainsString('19:30', $rendered);
        $this->assertStringContainsString('21:00', $rendered);
        $this->assertStringContainsString('Culto de exemplo', $rendered);
        $this->assertStringContainsString('Embu das Artes', $rendered);
    }

    public function test_manager_renames_a_type_and_creates_another(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(CcbReferenceDataSeeder::class);

        $user = User::factory()->create(['is_super_admin' => true]);
        $reuniao = EventType::query()->where('slug', 'reuniao')->firstOrFail();

        Livewire::actingAs($user)
            ->test(EventTypeTemplateManager::class)
            ->call('edit', $reuniao->id)
            ->set('name', 'Reunião regional')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Reunião regional', $reuniao->fresh()->name);
        $this->assertSame('reuniao', $reuniao->fresh()->slug);

        Livewire::actingAs($user)
            ->test(EventTypeTemplateManager::class)
            ->call('startCreate')
            ->set('name', 'Reunião de ensaio local')
            ->set('report_layout', 'narrative')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Reunião de ensaio local');

        $created = EventType::query()->where('name', 'Reunião de ensaio local')->first();
        $this->assertNotNull($created);
        $this->assertSame('reuniao-de-ensaio-local', $created->slug);
        $this->assertTrue($created->is_active);
    }

    public function test_templates_page_requires_permission(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create(['is_super_admin' => false]);

        $this->actingAs($user)
            ->get(route('agenda.event-type-templates'))
            ->assertForbidden();
    }
}
