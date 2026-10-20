<?php

namespace Tests\Feature;

use App\Models\Administration;
use App\Models\PrayerHouse;
use App\Models\PublicDepartment;
use App\Models\PublicGroup;
use App\Models\PublicSubgroup;
use App\Models\Regional;
use App\Services\PublicAudience\PublicCatalogOrganogramSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCatalogOrganogramSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_canonical_organogram_for_regional(): void
    {
        $regional = Regional::query()->create([
            'name' => 'Regional Org',
            'slug' => 'regional-org',
            'is_active' => true,
        ]);

        $admin = Administration::query()->create([
            'regional_id' => $regional->id,
            'name' => 'Admin Org',
            'slug' => 'admin-org',
            'is_active' => true,
        ]);

        $house = PrayerHouse::query()->create([
            'administration_id' => $admin->id,
            'name' => 'Casa Org',
            'slug' => 'casa-org',
            'is_active' => true,
        ]);

        (new PublicCatalogOrganogramSeeder)->seedForRegional($regional);

        $this->assertSame(4, PublicGroup::query()->where('regional_id', $regional->id)->count());

        $this->assertSame(5, PublicSubgroup::query()
            ->whereHas('group', fn ($q) => $q
                ->where('regional_id', $regional->id)
                ->where('slug', PublicCatalogOrganogramSeeder::ADMIN_GROUP_SLUG))
            ->where('slug', 'like', 'grupo-%')
            ->count());

        $this->assertSame(17, PublicDepartment::query()
            ->where('scope', PublicDepartment::SCOPE_REGIONAL)
            ->where('regional_id', $regional->id)
            ->where('meta->axis', 'administrative')
            ->whereNotNull('public_subgroup_id')
            ->count());

        $operationalSlugs = [
            'tesouraria-operacional',
            'ativo-imobilizado',
            'voluntario',
            'manutencao-preventiva',
            'cns-limpeza',
            'saude',
            'seguranca-operacional',
        ];

        foreach ($operationalSlugs as $slug) {
            $this->assertTrue(
                PublicDepartment::query()
                    ->where('scope', PublicDepartment::SCOPE_REGIONAL)
                    ->where('regional_id', $regional->id)
                    ->where('slug', $slug)
                    ->where('meta->axis', 'operational')
                    ->exists(),
                "Missing operational regional dept: {$slug}"
            );

            $this->assertTrue(
                PublicDepartment::query()
                    ->where('scope', PublicDepartment::SCOPE_PRAYER_HOUSE)
                    ->where('prayer_house_id', $house->id)
                    ->where('slug', $slug)
                    ->exists(),
                "Missing operational house dept: {$slug}"
            );
        }

        $this->assertTrue(
            PublicDepartment::query()
                ->where('regional_id', $regional->id)
                ->where('slug', 'tesouraria')
                ->where('meta->axis', 'administrative')
                ->exists()
        );

        $this->assertFalse(
            PublicDepartment::query()
                ->where('prayer_house_id', $house->id)
                ->where('slug', 'tesouraria')
                ->exists()
        );

        $ministryDept = PublicDepartment::query()
            ->where('scope', PublicDepartment::SCOPE_REGIONAL)
            ->where('regional_id', $regional->id)
            ->where('slug', 'ministerio')
            ->first();

        $this->assertNotNull($ministryDept);
        $this->assertSame('ministry', $ministryDept->meta['axis'] ?? null);
        $this->assertNotNull($ministryDept->public_subgroup_id);

        $musicDept = PublicDepartment::query()
            ->where('scope', PublicDepartment::SCOPE_ADMINISTRATION)
            ->where('administration_id', $admin->id)
            ->where('slug', 'musica')
            ->first();

        $this->assertNotNull($musicDept);
        $this->assertSame('music', $musicDept->meta['axis'] ?? null);
        $this->assertNotNull($musicDept->public_subgroup_id);
    }

    public function test_purge_and_reseed_is_idempotent(): void
    {
        $regional = Regional::query()->create([
            'name' => 'Regional Idem',
            'slug' => 'regional-idem',
            'is_active' => true,
        ]);

        $seeder = new PublicCatalogOrganogramSeeder;
        $first = $seeder->seedForRegional($regional);
        $seeder->purgeForRegional($regional);
        $second = $seeder->seedForRegional($regional);

        $this->assertGreaterThan(0, $first['departments']);
        $this->assertSame($first['departments'], $second['departments']);
    }
}
