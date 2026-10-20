<?php

namespace Tests\Feature;

use App\Livewire\Agenda\EventForm;
use App\Models\Event;
use App\Models\EventRoleAssignment;
use App\Models\EventRoleTemplate;
use App\Models\EventType;
use App\Models\MinistryServant;
use App\Models\PublicDepartment;
use App\Models\PublicPosition;
use App\Models\Regional;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\CcbReferenceDataSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EventFormAttendantTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving_the_event_marks_a_registered_servant(): void
    {
        [$user, $regional, $event] = $this->seedEvent();
        $servant = MinistryServant::register($regional->id, 'Paulo Mendes', $this->ministryOffice($regional, 'Diácono', 'diacono')->id, '11912345678');

        Livewire::actingAs($user)
            ->test(EventForm::class, ['event' => $event])
            ->set('ministry_servant_id', (string) $servant->id)
            ->call('saveDraft')
            ->assertHasNoErrors()
            ->assertRedirect(route('agenda.events.index'));

        $assignment = EventRoleAssignment::query()->where('event_id', $event->id)->first();
        $this->assertSame('Paulo Mendes', $assignment?->assignee_label);
        $this->assertSame($servant->id, data_get($assignment?->meta, 'ministry_servant_id'));
    }

    public function test_clearing_the_select_removes_the_marking(): void
    {
        [$user, $regional, $event] = $this->seedEvent();
        $servant = MinistryServant::register($regional->id, 'Paulo Mendes', $this->ministryOffice($regional)->id, '');
        $servant->assignTo($event);

        Livewire::actingAs($user)
            ->test(EventForm::class, ['event' => $event->fresh()])
            ->assertSet('ministry_servant_id', (string) $servant->id)
            ->set('ministry_servant_id', '')
            ->call('saveDraft')
            ->assertHasNoErrors();

        $this->assertSame(0, EventRoleAssignment::query()->where('event_id', $event->id)->count());
    }

    public function test_a_free_text_name_that_matches_the_registry_is_selected(): void
    {
        [$user, $regional, $event] = $this->seedEvent();
        $servant = MinistryServant::register($regional->id, 'Maria dos Santos', $this->ministryOffice($regional)->id, '');
        $event->roleAssignments()->create([
            'event_role_template_id' => EventRoleTemplate::query()->where('slug', 'atende')->value('id'),
            'assignee_label' => 'Maria dos Santos',
        ]);

        Livewire::actingAs($user)
            ->test(EventForm::class, ['event' => $event->fresh()])
            ->assertSet('ministry_servant_id', (string) $servant->id)
            ->assertSee('Maria dos Santos · Ancião');
    }

    public function test_an_unknown_free_text_name_stays_until_a_person_is_chosen(): void
    {
        [$user, , $event] = $this->seedEvent();
        $event->roleAssignments()->create([
            'event_role_template_id' => EventRoleTemplate::query()->where('slug', 'atende')->value('id'),
            'assignee_label' => 'Nome antigo',
        ]);

        Livewire::actingAs($user)
            ->test(EventForm::class, ['event' => $event->fresh()])
            ->assertSet('ministry_servant_id', 'legacy')
            ->assertSee('Nome antigo (nome livre — escolha o cadastro)')
            ->call('saveDraft')
            ->assertHasNoErrors();

        $assignment = EventRoleAssignment::query()->where('event_id', $event->id)->first();
        $this->assertSame('Nome antigo', $assignment?->assignee_label);
        $this->assertNull(data_get($assignment?->meta, 'ministry_servant_id'));
    }

    public function test_registering_from_the_form_selects_the_new_person(): void
    {
        [$user, $regional, $event] = $this->seedEvent();
        $office = $this->ministryOffice($regional);

        Livewire::actingAs($user)
            ->test(EventForm::class, ['event' => $event])
            ->set('servant_name', 'José da Silva')
            ->set('servant_position_id', (string) $office->id)
            ->set('servant_phone', '')
            ->call('registerAttendant')
            ->assertHasNoErrors()
            ->assertSet('ministry_servant_id', (string) MinistryServant::query()->where('full_name', 'José da Silva')->value('id'));
    }

    public function test_a_servant_from_another_regional_is_rejected(): void
    {
        [$user, $regional, $event] = $this->seedEvent();
        $other = Regional::query()->create([
            'name' => 'Outra Regional',
            'slug' => 'outra-regional',
            'is_active' => true,
        ]);
        $servant = MinistryServant::register($other->id, 'Paulo Mendes', $this->ministryOffice($other)->id, '');

        Livewire::actingAs($user)
            ->test(EventForm::class, ['event' => $event])
            ->set('ministry_servant_id', (string) $servant->id)
            ->call('saveDraft')
            ->assertHasErrors(['ministry_servant_id']);

        $this->assertSame(0, EventRoleAssignment::query()->where('event_id', $event->id)->count());
        $this->assertNotSame($other->id, $regional->id);
    }

    /**
     * @return array{0: User, 1: Regional, 2: Event}
     */
    protected function seedEvent(): array
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
        $event = Event::query()->create([
            'regional_id' => $regional->id,
            'title' => 'Batismo de hoje',
            'event_type_id' => EventType::query()->where('slug', 'batismo')->firstOrFail()->id,
            'starts_at' => Carbon::parse('2026-10-19 19:00:00'),
            'ends_at' => Carbon::parse('2026-10-19 21:00:00'),
            'status' => 'published',
            'created_by' => $user->id,
        ]);

        return [$user, $regional, $event];
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
}
