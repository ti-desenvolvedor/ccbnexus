<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\MinistryServant;
use App\Models\PublicDepartment;
use App\Models\PublicPosition;
use App\Models\Regional;
use App\Services\MarkingsReportBuilder;
use Illuminate\Database\Seeder;

class EmbuMinistryServantsSeeder extends Seeder
{
    public function run(): void
    {
        $regional = Regional::query()->where('slug', 'embu-das-artes')->first();
        if (! $regional) {
            return;
        }

        /** @var array{names: list<string>, markings: list<array{name: string, on: string, type: string, location: string}>} $data */
        $data = require database_path('seeders/data/embu_ministry_servants_2026.php');
        $position = $this->anciaoPosition($regional);
        $servants = $this->registerNames($regional->id, $position->id, $data['names']);

        $this->assignMarkings($regional, $servants, $data['markings']);
        $this->assignLabeledEvents($regional, $servants);
    }

    private function anciaoPosition(Regional $regional): PublicPosition
    {
        $department = PublicDepartment::query()->firstOrCreate(
            [
                'scope' => PublicDepartment::SCOPE_REGIONAL,
                'regional_id' => $regional->id,
                'slug' => 'ministerio',
            ],
            [
                'name' => 'Ministério',
                'is_active' => true,
                'sort_order' => 10,
            ],
        );

        return PublicPosition::query()->firstOrCreate(
            [
                'public_department_id' => $department->id,
                'slug' => 'anciao',
            ],
            [
                'name' => 'Ancião',
                'is_active' => true,
                'sort_order' => 10,
            ],
        );
    }

    /**
     * @param  list<string>  $names
     * @return array<string, MinistryServant>
     */
    private function registerNames(int $regionalId, int $positionId, array $names): array
    {
        $servants = [];
        foreach ($names as $name) {
            $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');
            if ($name === '' || ! str_contains($name, ' ') || mb_strlen($name) < 5) {
                continue;
            }

            $key = MinistryServant::nameKey($name);
            if (isset($servants[$key])) {
                continue;
            }

            $existing = MinistryServant::query()
                ->where('regional_id', $regionalId)
                ->where('name_key', $key)
                ->first();

            $servants[$key] = $existing ?? MinistryServant::register($regionalId, $name, $positionId, '');
        }

        return $servants;
    }

    /**
     * @param  array<string, MinistryServant>  $servants
     * @param  list<array{name: string, on: string, type: string, location: string}>  $markings
     */
    private function assignMarkings(Regional $regional, array $servants, array $markings): void
    {
        $bras = app(MarkingsReportBuilder::class);

        foreach ($markings as $marking) {
            $servant = $servants[MinistryServant::nameKey($marking['name'])] ?? null;
            if (! $servant) {
                continue;
            }

            $events = Event::query()
                ->with('location')
                ->where('regional_id', $regional->id)
                ->whereNotIn('status', ['cancelled', 'rejected'])
                ->whereDate('starts_at', $marking['on'])
                ->whereHas('eventType', fn ($type) => $type->where('slug', $marking['type']))
                ->whereHas('location', fn ($location) => $location->where('name', $marking['location']))
                ->get();

            foreach ($events as $event) {
                if ($bras->locationIsBras($event)) {
                    continue;
                }

                $servant->assignTo($event);
            }
        }
    }

    /**
     * @param  array<string, MinistryServant>  $servants
     */
    private function assignLabeledEvents(Regional $regional, array $servants): void
    {
        $bras = app(MarkingsReportBuilder::class);

        $events = Event::query()
            ->with(['location', 'roleAssignments.roleTemplate'])
            ->where('regional_id', $regional->id)
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->whereHas('roleAssignments', fn ($assignment) => $assignment->whereHas(
                'roleTemplate',
                fn ($template) => $template->where('slug', 'atende'),
            ))
            ->get();

        foreach ($events as $event) {
            if ($bras->locationIsBras($event)) {
                continue;
            }

            $label = $event->roleAssignments
                ->first(fn ($assignment) => $assignment->roleTemplate?->slug === 'atende')
                ?->assignee_label;

            $servant = $servants[MinistryServant::nameKey((string) $label)] ?? null;
            if (! $servant) {
                continue;
            }

            $servant->assignTo($event);
        }
    }
}
