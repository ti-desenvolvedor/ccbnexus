<?php

namespace Tests\Feature\Security;

use App\Models\AccessRequest;
use App\Models\Administration;
use App\Models\MeetingRoom;
use App\Models\Regional;
use App\Models\User;
use App\Services\AccessRequestService;
use App\Services\EventService;
use App\Services\RoomReservationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NexusHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_route_absent_by_default(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_event_publish_requires_aprovar_evento(): void
    {
        $this->seedPermissions();
        $regional = Regional::query()->create(['name' => 'R1', 'slug' => 'r1', 'is_active' => true]);
        $user = User::factory()->create(['regional_id' => $regional->id]);
        $user->givePermissionTo('criar_evento');

        $event = app(EventService::class)->store([
            'regional_id' => $regional->id,
            'title' => 'Teste',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
            'status' => 'published',
            'attendance_mode' => 'in_person',
        ], $user);

        $this->assertSame('pending_approval', $event->status);
    }

    public function test_reservation_without_approve_permission_stays_pending(): void
    {
        $this->seedPermissions();
        $regional = Regional::query()->create(['name' => 'R2', 'slug' => 'r2', 'is_active' => true]);
        $admin = Administration::query()->create([
            'regional_id' => $regional->id,
            'name' => 'Adm',
            'slug' => 'adm',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'regional_id' => $regional->id,
            'administration_id' => $admin->id,
        ]);
        $user->givePermissionTo('criar_reserva_sala');

        $room = MeetingRoom::query()->create([
            'name' => 'Sala A',
            'slug' => 'sala-a',
            'owner_type' => Administration::class,
            'owner_id' => $admin->id,
            'is_active' => true,
        ]);

        $reservation = app(RoomReservationService::class)->create([
            'meeting_room_id' => $room->id,
            'title' => 'Ensaio',
            'starts_at' => Carbon::now()->addDay()->setHour(10),
            'ends_at' => Carbon::now()->addDay()->setHour(11),
            'requires_approval' => false,
        ], $user);

        $this->assertSame('pending', $reservation->status);
        $this->assertTrue($reservation->requires_approval);
    }

    public function test_access_approve_provisions_user(): void
    {
        $this->seedPermissions();
        $regional = Regional::query()->create(['name' => 'R3', 'slug' => 'r3', 'is_active' => true]);
        Role::findOrCreate('Visualizador', 'web');

        $reviewer = User::factory()->create(['regional_id' => $regional->id]);
        $reviewer->forceFill(['is_super_admin' => true])->save();
        $reviewer->givePermissionTo('aprovar_acesso');

        $req = AccessRequest::query()->create([
            'name' => 'Novo',
            'email' => 'novo@example.com',
            'status' => 'pending',
            'regional_id' => $regional->id,
        ]);

        $user = app(AccessRequestService::class)->approve($req, $reviewer, 'ok regional');

        $this->assertDatabaseHas('users', ['email' => 'novo@example.com', 'regional_id' => $regional->id]);
        $this->assertSame('approved', $req->fresh()->status);
        $this->assertTrue($user->hasRole('Visualizador'));
    }

    public function test_status_endpoint_ok(): void
    {
        $this->getJson('/status')->assertOk()->assertJsonStructure(['app', 'ok', 'time']);
    }

    protected function seedPermissions(): void
    {
        foreach ([
            'criar_evento', 'aprovar_evento', 'criar_reserva_sala', 'aprovar_reserva_sala', 'aprovar_acesso',
        ] as $p) {
            Permission::findOrCreate($p, 'web');
        }
    }
}
