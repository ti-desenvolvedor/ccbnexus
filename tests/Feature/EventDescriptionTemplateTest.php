<?php

namespace Tests\Feature;

use App\Livewire\Agenda\EventForm;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Location;
use App\Models\Regional;
use App\Models\User;
use App\Services\BaptismListReportBuilder;
use App\Services\EventTypeAudiencePresetService;
use Carbon\Carbon;
use Database\Seeders\CcbReferenceDataSeeder;
use Database\Seeders\EventTypeAudiencePresetSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EventDescriptionTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_description_keeps_template_codes_and_preview_follows_the_date(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(CcbReferenceDataSeeder::class);
        $this->seed(EventTypeAudiencePresetSeeder::class);

        $user = User::factory()->create(['is_super_admin' => true]);
        $rrm = EventType::query()->where('slug', 'rrm')->firstOrFail();
        $template = (string) $rrm->audiencePreset->invitation_paragraph;

        Livewire::actingAs($user)
            ->test(EventForm::class)
            ->set('title', 'RRM de teste')
            ->set('starts_at', '2026-11-08T19:30')
            ->set('ends_at', '2026-11-08T21:00')
            ->set('event_type_id', $rrm->id)
            ->assertSet('description', $template)
            ->assertSee('{day_month}')
            ->assertSee('08/11')
            ->set('starts_at', '2026-12-02T19:30')
            ->assertSet('description', $template)
            ->assertSee('02/12')
            ->assertDontSee('08/11');
    }

    public function test_changing_the_model_updates_descriptions_that_still_use_it(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(CcbReferenceDataSeeder::class);
        $this->seed(EventTypeAudiencePresetSeeder::class);

        $user = User::factory()->create(['is_super_admin' => true]);
        $regional = Regional::query()->create([
            'name' => 'Regional Teste',
            'slug' => 'regional-teste-descricao',
            'is_active' => true,
        ]);
        $rrm = EventType::query()->where('slug', 'rrm')->firstOrFail();
        $previous = (string) $rrm->audiencePreset->invitation_paragraph;

        $linked = Event::query()->create([
            'regional_id' => $regional->id,
            'title' => 'RRM ligado ao modelo',
            'description' => $previous,
            'event_type_id' => $rrm->id,
            'starts_at' => Carbon::parse('2026-11-08 19:30:00'),
            'ends_at' => Carbon::parse('2026-11-08 21:00:00'),
            'status' => 'published',
            'created_by' => $user->id,
        ]);
        $custom = Event::query()->create([
            'regional_id' => $regional->id,
            'title' => 'RRM com texto próprio',
            'description' => 'Texto escrito à mão, sem códigos.',
            'event_type_id' => $rrm->id,
            'starts_at' => Carbon::parse('2026-11-09 19:30:00'),
            'ends_at' => Carbon::parse('2026-11-09 21:00:00'),
            'status' => 'published',
            'created_by' => $user->id,
        ]);

        $next = 'No dia {day_month}, {weekday}, reunião atualizada em {location}.';
        app(EventTypeAudiencePresetService::class)->saveParagraph($rrm->id, $next);

        $this->assertSame($next, $linked->fresh()->description);
        $this->assertSame('Texto escrito à mão, sem códigos.', $custom->fresh()->description);
    }

    public function test_report_fills_description_codes_from_the_event(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(CcbReferenceDataSeeder::class);
        $this->seed(EventTypeAudiencePresetSeeder::class);

        $regional = Regional::query()->create([
            'name' => 'Regional Batismo',
            'slug' => 'regional-batismo-descricao',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'regional_id' => $regional->id,
            'is_super_admin' => true,
        ]);
        $location = Location::query()->create(['name' => 'Casa Central']);
        $batismo = EventType::query()->where('slug', 'batismo')->firstOrFail();

        Event::query()->create([
            'regional_id' => $regional->id,
            'title' => 'Batismo',
            'description' => 'Batismo em {location}, no dia {date}, às {time}.',
            'event_type_id' => $batismo->id,
            'location_id' => $location->id,
            'starts_at' => Carbon::parse('2026-11-08 19:30:00'),
            'ends_at' => Carbon::parse('2026-11-08 21:00:00'),
            'status' => 'published',
            'include_on_baptism_list' => true,
            'created_by' => $user->id,
        ]);

        $report = app(BaptismListReportBuilder::class)->generateOrSync(
            $regional->id,
            Carbon::parse('2026-10-15'),
            $user,
        );

        $item = $report->items->firstWhere('section_key', 'batismos');
        $this->assertNotNull($item);
        $description = (string) ($item->payload['description'] ?? '');
        $this->assertStringContainsString('Casa Central', $description);
        $this->assertStringContainsString('08/11/2026', $description);
        $this->assertStringContainsString('19:30', $description);
        $this->assertStringNotContainsString('{location}', $description);
    }
}
