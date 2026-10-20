<?php

namespace Tests\Feature;

use App\Livewire\Agenda\EventList;
use App\Livewire\Context\OrganizationalContextSelector;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Regional;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EventListContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_ignores_inactive_session_regional_and_lists_embu_events(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $demo = Regional::query()->create([
            'name' => 'Regional demonstração',
            'slug' => 'ccb-demo',
            'is_active' => false,
        ]);
        $embu = Regional::query()->create([
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

        $user = User::factory()->create([
            'regional_id' => $embu->id,
            'is_super_admin' => true,
        ]);

        Event::query()->create([
            'regional_id' => $embu->id,
            'title' => 'Batismo EA - Central',
            'event_type_id' => $type->id,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHours(2),
            'status' => 'published',
            'is_occurrence' => false,
            'created_by' => $user->id,
        ]);

        session(['nexus.active_regional_id' => $demo->id]);

        $this->actingAs($user);

        Livewire::test(EventList::class)
            ->set('view', 'list')
            ->set('listMode', 'series')
            ->assertSee('Batismo EA - Central')
            ->assertSee('Regional Embu das Artes');
    }

    public function test_changing_active_regional_filters_the_event_list(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

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
        $type = EventType::query()->create([
            'name' => 'Batismo',
            'slug' => 'batismo',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $user = User::factory()->create([
            'regional_id' => $embu->id,
            'is_super_admin' => true,
        ]);

        Event::query()->create([
            'regional_id' => $embu->id,
            'title' => 'Batismo que deve aparecer',
            'event_type_id' => $type->id,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHours(2),
            'status' => 'published',
            'is_occurrence' => false,
            'created_by' => $user->id,
        ]);

        session(['nexus.active_regional_id' => $demo->id]);
        $this->actingAs($user);

        Livewire::test(EventList::class)
            ->set('view', 'list')
            ->set('listMode', 'series')
            ->assertDontSee('Batismo que deve aparecer')
            ->assertSee('Regional demonstração');

        Livewire::test(OrganizationalContextSelector::class)
            ->set('regionalId', $embu->id)
            ->assertHasNoErrors()
            ->assertDispatched('nexus-context-updated');

        $this->assertSame($embu->id, session('nexus.active_regional_id'));

        Livewire::test(EventList::class)
            ->set('view', 'list')
            ->set('listMode', 'series')
            ->assertSee('Batismo que deve aparecer')
            ->assertSee('Regional Embu das Artes');
    }

    public function test_compact_context_selector_keeps_regional_control_inside_the_livewire_root(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $embu = Regional::query()->create([
            'name' => 'Regional Embu das Artes',
            'slug' => 'embu-das-artes',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'regional_id' => $embu->id,
            'is_super_admin' => true,
        ]);
        $this->actingAs($user);

        $html = Livewire::test(OrganizationalContextSelector::class, ['compact' => true])->html();
        $document = new \DOMDocument;
        $document->loadHTML('<?xml encoding="utf-8" ?>'.$html);

        $root = null;
        foreach ($document->getElementsByTagName('*') as $element) {
            if ($element->hasAttribute('wire:id')) {
                $root = $element;
                break;
            }
        }

        $this->assertNotNull($root);
        $this->assertSame('div', strtolower($root->nodeName));

        $bound = false;
        foreach ($root->getElementsByTagName('select') as $select) {
            if ($select->getAttribute('aria-label') === 'Regional') {
                $bound = true;
            }
        }

        $this->assertTrue($bound);
    }
}
