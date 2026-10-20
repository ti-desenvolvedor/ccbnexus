<?php

use App\Http\Controllers\AgendaMonthPdfController;
use App\Http\Controllers\BaptismListDocxController;
use App\Http\Controllers\BaptismListPdfController;
use App\Http\Controllers\EventReportExportController;
use App\Http\Controllers\MarkingsReportExportController;
use App\Models\Administration;
use App\Models\Audience;
use App\Models\Event;
use App\Models\Location;
use App\Models\MeetingRoom;
use App\Models\Parking;
use App\Models\PrayerHouse;
use App\Models\PublicDepartment;
use App\Models\PublicGroup;
use App\Models\PublicPosition;
use App\Models\PublicSubgroup;
use App\Models\Regional;
use App\Models\RoomReservation;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::view('/', 'home')->name('home');

Route::get('/status', function () {
    $payload = [
        'app' => config('app.name'),
        'ok' => true,
        'time' => now()->toIso8601String(),
    ];

    if (! app()->environment('production')) {
        $payload['env'] = config('app.env');
    }

    return response()->json($payload);
})->name('status');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::view('/accesso/solicitar', 'pages.public.access-request')
    ->middleware('throttle:10,1')
    ->name('access.request');

Route::middleware(['auth', 'verified', 'nexus.context'])->prefix('agenda')->group(function () {
    Route::view('/events', 'pages.agenda.events-index')->name('agenda.events.index');
    Route::view('/events/create', 'pages.agenda.events-editor', ['event' => null])->name('agenda.events.create');
    Route::get('/events/pdf', AgendaMonthPdfController::class)->name('agenda.events.pdf');
    Route::get('/events/{event}/edit', fn (Event $event) => view('pages.agenda.events-editor', compact('event')))->name('agenda.events.edit');
    Route::get('/events/{event}/confirmacao', fn (Event $event) => view('pages.agenda.event-rsvp', compact('event')))->name('agenda.events.rsvp');
    Route::view('/whatsapp', 'pages.agenda.whatsapp-notifications-index')->name('agenda.whatsapp.index');
    Route::view('/whatsapp/templates', 'pages.agenda.whatsapp-templates-index')->name('agenda.whatsapp-templates.index');
    Route::view('/modelos-de-evento', 'pages.agenda.event-type-templates')->name('agenda.event-type-templates');

    Route::view('/approvals', 'pages.agenda.approvals-index')->name('agenda.approvals.index');

    Route::view('/audiences', 'pages.agenda.audiences-index')->name('agenda.audiences.index');
    Route::view('/audiences/create', 'pages.agenda.audiences-editor', ['audience' => null])->name('agenda.audiences.create');
    Route::get('/audiences/{audience}/edit', fn (Audience $audience) => view('pages.agenda.audiences-editor', compact('audience')))->name('agenda.audiences.edit');

    Route::view('/public-catalog', 'pages.agenda.public-catalog-index')->name('agenda.public-catalog.index');
    Route::view('/public-catalog/administrative', 'pages.agenda.public-catalog.administrative-structure')->name('agenda.public-catalog.administrative');
    Route::view('/public-catalog/operational-services', 'pages.agenda.public-catalog.operational-services')->name('agenda.public-catalog.operational-services');
    Route::view('/public-catalog/ministry', 'pages.agenda.public-catalog.ministry')->name('agenda.public-catalog.ministry');
    Route::view('/public-catalog/music', 'pages.agenda.public-catalog.music')->name('agenda.public-catalog.music');
    Route::view('/public-catalog/groups', 'pages.agenda.public-groups-index')->name('agenda.public-groups.index');
    Route::view('/public-catalog/groups/create', 'pages.agenda.public-groups-editor', ['publicGroup' => null])->name('agenda.public-groups.create');
    Route::get('/public-catalog/groups/{group}/edit', fn (PublicGroup $group) => view('pages.agenda.public-groups-editor', ['publicGroup' => $group]))->name('agenda.public-groups.edit');
    Route::get('/public-catalog/groups/{group}/subgroups', fn (PublicGroup $group) => view('pages.agenda.public-subgroups-index', compact('group')))->name('agenda.public-subgroups.index');
    Route::get('/public-catalog/groups/{group}/subgroups/create', fn (PublicGroup $group) => view('pages.agenda.public-subgroups-editor', compact('group')))->name('agenda.public-subgroups.create');
    Route::get('/public-catalog/groups/{group}/subgroups/{subgroup}/edit', function (PublicGroup $group, PublicSubgroup $subgroup) {
        abort_unless($subgroup->public_group_id === $group->id, 404);

        return view('pages.agenda.public-subgroups-editor', compact('group', 'subgroup'));
    })->name('agenda.public-subgroups.edit');
    Route::get('/public-catalog/groups/{group}/subgroups/{subgroup}/departments', function (PublicGroup $group, PublicSubgroup $subgroup) {
        abort_unless($subgroup->public_group_id === $group->id, 404);

        return view('pages.agenda.public-catalog.subgroup-departments', compact('group', 'subgroup'));
    })->name('agenda.public-subgroup.departments');

    Route::view('/public-catalog/departments', 'pages.agenda.public-departments-index')->name('agenda.public-departments.index');
    Route::view('/public-catalog/departments/create', 'pages.agenda.public-departments-editor', ['department' => null])->name('agenda.public-departments.create');
    Route::get('/public-catalog/departments/{department}/edit', fn (PublicDepartment $department) => view('pages.agenda.public-departments-editor', compact('department')))->name('agenda.public-departments.edit');

    Route::view('/public-catalog/positions', 'pages.agenda.public-positions-index')->name('agenda.public-positions.index');
    Route::view('/public-catalog/positions/create', 'pages.agenda.public-positions-editor', ['position' => null])->name('agenda.public-positions.create');
    Route::get('/public-catalog/positions/{position}/edit', fn (PublicPosition $position) => view('pages.agenda.public-positions-editor', compact('position')))->name('agenda.public-positions.edit');
});

Route::middleware(['auth', 'verified', 'can:visualizar_relatorios'])->group(function () {
    Route::get('/reports/events.csv', EventReportExportController::class)->name('reports.events.csv');
    Route::view('/secretaria/lista-batismos', 'pages.secretaria.baptism-list')->name('secretaria.baptism-list');
    Route::view('/secretaria/marcacoes', 'pages.secretaria.markings')->name('secretaria.markings');
    Route::view('/secretaria/quem-atende', 'pages.secretaria.quem-atende')->name('secretaria.quem-atende');
    Route::get('/secretaria/marcacoes/export/{format}', MarkingsReportExportController::class)
        ->whereIn('format', ['pdf', 'xlsx'])
        ->name('secretaria.markings.export');
    Route::get('/secretaria/lista-batismos/{report}/pdf', BaptismListPdfController::class)
        ->name('secretaria.baptism-list.pdf');
    Route::get('/secretaria/lista-batismos/{report}/docx', BaptismListDocxController::class)
        ->name('secretaria.baptism-list.docx');
});

Route::middleware(['auth', 'verified'])->prefix('secretaria')->name('secretaria.')->group(function () {
    Route::view('/avisos', 'pages.secretaria.notices')->name('notices');
    Route::view('/alteracoes', 'pages.secretaria.schedule-changes')->name('schedule-changes');
    Route::view('/coletas', 'pages.secretaria.collections')->name('collections');
    Route::view('/grupos-whatsapp', 'pages.secretaria.whatsapp-groups')->name('whatsapp-groups');
});

Route::middleware(['auth', 'verified', 'can:aprovar_acesso'])->group(function () {
    Route::view('/access/requests', 'pages.access.requests-index')->name('access.requests.index');
});

Route::middleware(['auth', 'verified'])->prefix('users')->name('users.')->group(function () {
    Route::view('/', 'pages.users.users-index')
        ->middleware('can:viewAny,'.User::class)
        ->name('index');

    Route::view('/create', 'pages.users.users-editor', ['user' => null])
        ->middleware('can:create,'.User::class)
        ->name('create');

    Route::get('/{user}/edit', fn (User $user) => view('pages.users.users-editor', compact('user')))
        ->middleware('can:update,user')
        ->name('edit');

    Route::view('/roles', 'pages.users.roles-directory')
        ->middleware('can:viewUserRoleDirectory')
        ->name('roles');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('/organization/regionals', 'pages.organization.regionals-index')
        ->middleware('can:viewAny,'.Regional::class)
        ->name('organization.regionals.index');
    Route::view('/organization/regionals/create', 'pages.organization.regionals-editor', ['regional' => null])
        ->middleware('can:create,'.Regional::class)
        ->name('organization.regionals.create');
    Route::get('/organization/regionals/{regional}/edit', fn (Regional $regional) => view('pages.organization.regionals-editor', compact('regional')))
        ->middleware('can:update,regional')
        ->name('organization.regionals.edit');

    Route::view('/organization/administrations', 'pages.organization.administrations-index')
        ->middleware('can:viewAny,'.Administration::class)
        ->name('organization.administrations.index');
    Route::view('/organization/administrations/create', 'pages.organization.administrations-editor', ['administration' => null])
        ->middleware('can:create,'.Administration::class)
        ->name('organization.administrations.create');
    Route::get('/organization/administrations/{administration}/edit', fn (Administration $administration) => view('pages.organization.administrations-editor', compact('administration')))
        ->middleware('can:update,administration')
        ->name('organization.administrations.edit');

    Route::view('/organization/prayer-houses', 'pages.organization.prayer-houses-index')
        ->middleware('can:viewAny,'.PrayerHouse::class)
        ->name('organization.prayer-houses.index');
    Route::view('/organization/prayer-houses/create', 'pages.organization.prayer-houses-editor', ['prayerHouse' => null])
        ->middleware('can:create,'.PrayerHouse::class)
        ->name('organization.prayer-houses.create');
    Route::get('/organization/prayer-houses/{prayerHouse}/edit', fn (PrayerHouse $prayerHouse) => view('pages.organization.prayer-houses-editor', compact('prayerHouse')))
        ->middleware('can:update,prayerHouse')
        ->name('organization.prayer-houses.edit');

    Route::view('/infrastructure/locations', 'pages.infrastructure.locations-index')
        ->middleware('can:viewAny,'.Location::class)
        ->name('infrastructure.locations.index');
    Route::view('/infrastructure/locations/create', 'pages.infrastructure.locations-editor', ['location' => null])
        ->middleware('can:create,'.Location::class)
        ->name('infrastructure.locations.create');
    Route::get('/infrastructure/locations/{location}/edit', fn (Location $location) => view('pages.infrastructure.locations-editor', compact('location')))
        ->middleware('can:update,location')
        ->name('infrastructure.locations.edit');

    Route::view('/infrastructure/meeting-rooms', 'pages.infrastructure.meeting-rooms-index')
        ->middleware('can:viewAny,'.MeetingRoom::class)
        ->name('infrastructure.meeting-rooms.index');
    Route::view('/infrastructure/meeting-rooms/create', 'pages.infrastructure.meeting-rooms-editor', ['meetingRoom' => null])
        ->middleware('can:create,'.MeetingRoom::class)
        ->name('infrastructure.meeting-rooms.create');
    Route::get('/infrastructure/meeting-rooms/{meetingRoom}/edit', fn (MeetingRoom $meetingRoom) => view('pages.infrastructure.meeting-rooms-editor', compact('meetingRoom')))
        ->middleware('can:update,meetingRoom')
        ->name('infrastructure.meeting-rooms.edit');

    Route::view('/infrastructure/room-reservations', 'pages.infrastructure.room-reservations-index')
        ->middleware('can:viewAny,'.RoomReservation::class)
        ->name('infrastructure.room-reservations.index');
    Route::view('/infrastructure/room-reservations/create', 'pages.infrastructure.room-reservations-create')
        ->middleware('can:create,'.RoomReservation::class)
        ->name('infrastructure.room-reservations.create');
    Route::view('/infrastructure/room-reservations/calendar', 'pages.infrastructure.room-reservations-calendar')
        ->middleware('can:viewAny,'.RoomReservation::class)
        ->name('infrastructure.room-reservations.calendar');

    Route::view('/infrastructure/parkings', 'pages.infrastructure.parkings-index')
        ->middleware('can:viewAny,'.Parking::class)
        ->name('infrastructure.parkings.index');
    Route::view('/infrastructure/parkings/create', 'pages.infrastructure.parkings-editor', ['parking' => null])
        ->middleware('can:create,'.Parking::class)
        ->name('infrastructure.parkings.create');
    Route::get('/infrastructure/parkings/{parking}/edit', fn (Parking $parking) => view('pages.infrastructure.parkings-editor', compact('parking')))
        ->middleware('can:update,parking')
        ->name('infrastructure.parkings.edit');
});

require __DIR__.'/auth.php';
