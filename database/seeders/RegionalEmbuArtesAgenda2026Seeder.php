<?php

namespace Database\Seeders;

use App\Models\Administration;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Location;
use App\Models\PrayerHouse;
use App\Models\Regional;
use App\Models\User;
use App\Models\WhatsAppGroup;
use App\Services\EventRecurrenceService;
use App\Services\Recurrence\AgendaSeriesStart;
use App\Services\SlugService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class RegionalEmbuArtesAgenda2026Seeder extends Seeder
{
    public function run(): void
    {
        $slugService = app(SlugService::class);
        $recurrence = app(EventRecurrenceService::class);

        $regional = Regional::query()->updateOrCreate(
            ['slug' => 'embu-das-artes'],
            ['name' => 'Regional Embu das Artes', 'is_active' => true],
        );

        $locations = $this->seedLocations($regional, $slugService);
        $creator = User::query()->where('is_super_admin', true)->first()
            ?? User::query()->first();

        WhatsAppGroup::query()->updateOrCreate(
            ['regional_id' => $regional->id, 'slug' => 'secretaria'],
            ['name' => 'Secretaria Regional', 'is_active' => true],
        );
        WhatsAppGroup::query()->updateOrCreate(
            ['regional_id' => $regional->id, 'slug' => 'ministerio'],
            ['name' => 'Ministério', 'is_active' => true],
        );
        WhatsAppGroup::query()->updateOrCreate(
            ['regional_id' => $regional->id, 'slug' => 'musica'],
            ['name' => 'Música', 'is_active' => true],
        );

        $types = EventType::query()->pluck('id', 'slug');
        $rows = $this->expandDateLists(require database_path('seeders/data/agenda_embu_artes_2026.php'));
        $starts = app(AgendaSeriesStart::class);
        $activeKeys = collect($rows)->pluck('excel_key')->filter()->values()->all();

        // Desativa séries antigas do seed que saíram do ficheiro (evita datas erradas no boletim).
        if ($activeKeys !== []) {
            $obsoleteParents = Event::query()
                ->where('regional_id', $regional->id)
                ->where('is_occurrence', false)
                ->whereNotNull('meta->excel_key')
                ->whereNotIn('meta->excel_key', $activeKeys)
                ->get();
            foreach ($obsoleteParents as $obsolete) {
                Event::query()
                    ->where('parent_event_id', $obsolete->id)
                    ->where('is_occurrence', true)
                    ->update([
                        'include_on_baptism_list' => false,
                        'status' => 'cancelled',
                    ]);
                $obsolete->update([
                    'include_on_baptism_list' => false,
                    'status' => 'cancelled',
                ]);
            }
        }

        foreach ($rows as $row) {
            $typeId = $types[$row['type']] ?? null;
            if (! $typeId) {
                continue;
            }
            $locationId = $locations[$row['location_key']] ?? null;
            $cfg = $row['recurrence'] ?? ['frequency' => 'none'];
            $frequency = (string) ($cfg['frequency'] ?? 'none');
            $recurrenceConfig = null;
            if ($frequency === 'monthly_nth') {
                $first = $starts->firstInYear(2026, (string) $row['time'], $cfg);
                $recurrenceConfig = [
                    'nth' => $cfg['nth'],
                    'weekday' => $cfg['weekday'],
                    'months' => $cfg['months'],
                ];
            } elseif ($frequency === 'yearly') {
                $first = Carbon::parse(trim($row['first'].' '.($row['time'] ?? '')));
                $recurrenceConfig = ['interval_years' => $cfg['interval_years'] ?? 1];
            } else {
                $first = Carbon::parse($row['on'].' '.$row['time']);
                $frequency = 'none';
            }
            $ends = $first->copy()->addHours((int) ($row['duration_hours'] ?? 2));

            $payload = [
                'regional_id' => $regional->id,
                'title' => $row['title'],
                'description' => $row['description'] ?? '',
                'event_type_id' => $typeId,
                'starts_at' => $first,
                'ends_at' => $ends,
                'location_id' => ($row['attendance_mode'] ?? null) === 'online_only' ? null : $locationId,
                'attendance_mode' => $row['attendance_mode'] ?? 'in_person',
                'status' => 'published',
                'created_by' => $creator?->id,
                'is_occurrence' => false,
                'parent_event_id' => null,
                'recurrence_frequency' => $frequency === 'none' ? null : $frequency,
                'recurrence_config' => $recurrenceConfig,
                'recurrence_until' => $frequency === 'none' ? null : sprintf('%d-12-31', max(2026, (int) now()->year) + 1),
                'recurrence_interval_weeks' => 1,
                'include_on_baptism_list' => (bool) ($row['bulletin'] ?? false),
                'whatsapp_enabled' => (bool) ($row['whatsapp'] ?? ! ($row['bulletin'] ?? false)),
                'has_libras' => (bool) ($row['has_libras'] ?? false),
                'meta' => [
                    'excel_key' => $row['excel_key'],
                    'excel_rule' => $cfg,
                    'excel_descritivo' => $row['descritivo'] ?? null,
                    'calendar_year_source' => 2026,
                ],
            ];

            $event = Event::query()
                ->where('regional_id', $regional->id)
                ->where('is_occurrence', false)
                ->where('meta->excel_key', $row['excel_key'])
                ->first();

            if ($event) {
                $event->update($payload);
            } else {
                $event = Event::query()->create($payload);
            }

            if (! empty($payload['whatsapp_enabled'])) {
                $groupIds = WhatsAppGroup::query()
                    ->where('regional_id', $regional->id)
                    ->whereIn('slug', ($row['type'] ?? '') === 'exame-musical' ? ['musica'] : ['secretaria', 'ministerio'])
                    ->pluck('id')
                    ->all();
                $event->whatsappGroups()->sync($groupIds);
            }

            $recurrence->syncWeeklyOccurrences($event->fresh());
        }
    }

    /**
     * Uma linha com várias datas avulsas (a grade não segue um único dia da semana)
     * vira um evento por data, sem recorrência.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    protected function expandDateLists(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $dates = $row['on_dates'] ?? null;
            if (! is_array($dates) || $dates === []) {
                $out[] = $row;

                continue;
            }

            foreach (array_values($dates) as $i => $date) {
                $copy = $row;
                unset($copy['on_dates']);
                $copy['on'] = $date;
                if ($i > 0) {
                    $copy['excel_key'] .= '-'.$date;
                }
                $out[] = $copy;
            }
        }

        return $out;
    }

    /**
     * @return array<string, int>
     */
    protected function seedLocations(Regional $regional, SlugService $slugService): array
    {
        $defs = [
            'ea-central' => ['name' => 'Embu das Artes - Central', 'city' => 'Embu das Artes'],
            'ea-jardim-da-luz' => ['name' => 'Jardim da Luz – Embu das Artes', 'city' => 'Embu das Artes'],
            'ea-kennedy' => ['name' => 'Jardim Presidente Kennedy – Embu das Artes', 'city' => 'Embu das Artes'],
            'ea-santa-tereza' => ['name' => 'Jardim Santa Tereza – Embu das Artes', 'city' => 'Embu das Artes'],
            'ea-vista-alegre' => ['name' => 'Jardim Vista Alegre – Embu das Artes', 'city' => 'Embu das Artes'],
            'eg-central' => ['name' => 'Embu-Guaçu - Central', 'city' => 'Embu-Guaçu'],
            'eg-cipo' => ['name' => 'Cipó-Guaçu – Embu-Guaçu', 'city' => 'Embu-Guaçu'],
            'its-jacira' => ['name' => 'Jardim Jacira – Itapecerica da Serra', 'city' => 'Itapecerica da Serra'],
            'its-marilu' => ['name' => 'Jardim Marilu – Itapecerica da Serra', 'city' => 'Itapecerica da Serra'],
            'its-pq-paraiso' => ['name' => 'Parque Paraíso – Itapecerica da Serra', 'city' => 'Itapecerica da Serra'],
            'its-vila-santa-maria' => ['name' => 'Vila Santa Maria – Itapecerica da Serra', 'city' => 'Itapecerica da Serra'],
            'its-crispim' => ['name' => 'Crispim – Itapecerica da Serra', 'city' => 'Itapecerica da Serra'],
            'juq-central' => ['name' => 'Juquitiba - Central', 'city' => 'Juquitiba'],
            'juq-barnabes' => ['name' => 'Barnabés – Juquitiba', 'city' => 'Juquitiba'],
            'juq-justinos' => ['name' => 'Justinos – Juquitiba', 'city' => 'Juquitiba'],
            'its-central' => ['name' => 'Itapecerica da Serra - Central', 'city' => 'Itapecerica da Serra'],
            'sls-central' => ['name' => 'São Lourenço da Serra - Central', 'city' => 'São Lourenço da Serra'],
            'tbs-iasi' => ['name' => 'Vila Iasi – Taboão da Serra', 'city' => 'Taboão da Serra'],
            'tbs-pirajussara' => ['name' => 'Pirajussara – Taboão da Serra', 'city' => 'Taboão da Serra'],
            'tbs-saint-moritz' => ['name' => 'Jardim Saint Moritz – Taboão da Serra', 'city' => 'Taboão da Serra'],
            'tbs-maria-rosa' => ['name' => 'Jardim Maria Rosa - Taboão da Serra', 'city' => 'Taboão da Serra'],
            'bras' => ['name' => 'Brás', 'city' => 'São Paulo'],
        ];

        $map = [];
        foreach ($defs as $key => $def) {
            $loc = Location::query()->updateOrCreate(
                ['regional_id' => $regional->id, 'name' => $def['name']],
                [
                    'city' => $def['city'],
                    'state' => 'SP',
                    'country' => 'BR',
                    'administration_id' => null,
                ],
            );
            $map[$key] = $loc->id;
        }

        $adm = Administration::query()->updateOrCreate(
            ['regional_id' => $regional->id, 'slug' => 'adm-embu-das-artes'],
            [
                'name' => 'Administração Embu das Artes',
                'location_id' => $map['ea-central'],
                'is_active' => true,
            ],
        );

        PrayerHouse::query()->updateOrCreate(
            ['administration_id' => $adm->id, 'slug' => 'ea-central'],
            [
                'name' => 'Embu das Artes - Central',
                'location_id' => $map['ea-central'],
                'is_active' => true,
            ],
        );

        return $map;
    }
}
