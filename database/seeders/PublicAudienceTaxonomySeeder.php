<?php

namespace Database\Seeders;

use App\Models\Administration;
use App\Models\PrayerHouse;
use App\Models\PublicDepartment;
use App\Models\PublicGroup;
use App\Models\PublicPosition;
use App\Models\PublicSubgroup;
use App\Models\Regional;
use Illuminate\Database\Seeder;

class PublicAudienceTaxonomySeeder extends Seeder
{
    public function run(): void
    {
        $regional = Regional::query()->where('slug', 'ccb-demo')->first();
        $administration = Administration::query()->where('slug', 'adm-demo')->first();
        $house = PrayerHouse::query()->where('slug', 'casa-demo')->first();

        if (! $regional || ! $administration || ! $house) {
            $this->command?->warn('PublicAudienceTaxonomySeeder: regional/admin/casa demo ausentes — execute MinimalOrganizationSeeder antes.');

            return;
        }

        $gAdmin = PublicGroup::query()->updateOrCreate(
            ['regional_id' => $regional->id, 'slug' => 'colaboradores-administrativos'],
            ['name' => 'Colaboradores administrativos', 'is_active' => true, 'sort_order' => 10, 'meta' => null],
        );

        $gLocais = PublicGroup::query()->updateOrCreate(
            ['regional_id' => $regional->id, 'slug' => 'colaboradores-locais'],
            ['name' => 'Colaboradores locais', 'is_active' => true, 'sort_order' => 20, 'meta' => null],
        );

        $gMinisterio = PublicGroup::query()->updateOrCreate(
            ['regional_id' => $regional->id, 'slug' => 'ministerio'],
            ['name' => 'Ministério', 'is_active' => true, 'sort_order' => 30, 'meta' => null],
        );

        $gMusica = PublicGroup::query()->updateOrCreate(
            ['regional_id' => $regional->id, 'slug' => 'musica'],
            ['name' => 'Música', 'is_active' => true, 'sort_order' => 40, 'meta' => null],
        );

        $gMusicaEnsaio = PublicGroup::query()->updateOrCreate(
            ['regional_id' => $regional->id, 'slug' => 'musica-ensaio-regional'],
            ['name' => 'Música — ensaio regional', 'is_active' => true, 'sort_order' => 50, 'meta' => null],
        );

        $sgEstatutarios = PublicSubgroup::query()->updateOrCreate(
            ['public_group_id' => $gAdmin->id, 'slug' => 'estatutarios'],
            ['name' => 'Colaboradores estatutários', 'is_active' => true, 'sort_order' => 10, 'meta' => null],
        );

        $sgAtivo = PublicSubgroup::query()->updateOrCreate(
            ['public_group_id' => $gAdmin->id, 'slug' => 'ativo-imobilizado'],
            ['name' => 'Ativo imobilizado', 'is_active' => true, 'sort_order' => 20, 'meta' => null],
        );

        $sgVoluntario = PublicSubgroup::query()->updateOrCreate(
            ['public_group_id' => $gAdmin->id, 'slug' => 'voluntario'],
            ['name' => 'Voluntário', 'is_active' => true, 'sort_order' => 30, 'meta' => null],
        );

        $sgSaude = PublicSubgroup::query()->updateOrCreate(
            ['public_group_id' => $gAdmin->id, 'slug' => 'saude'],
            ['name' => 'Saúde', 'is_active' => true, 'sort_order' => 40, 'meta' => null],
        );

        $sgLocaisTransversal = PublicSubgroup::query()->updateOrCreate(
            ['public_group_id' => $gLocais->id, 'slug' => 'transversal-local'],
            ['name' => 'Funções transversais (local)', 'is_active' => true, 'sort_order' => 10, 'meta' => null],
        );

        $deptPresRegional = PublicDepartment::query()->updateOrCreate(
            ['scope' => PublicDepartment::SCOPE_REGIONAL, 'regional_id' => $regional->id, 'slug' => 'presidencia-regional'],
            [
                'administration_id' => null,
                'prayer_house_id' => null,
                'name' => 'Presidência (regional)',
                'is_active' => true,
                'sort_order' => 10,
                'meta' => null,
            ],
        );

        $deptSecRegional = PublicDepartment::query()->updateOrCreate(
            ['scope' => PublicDepartment::SCOPE_REGIONAL, 'regional_id' => $regional->id, 'slug' => 'secretaria-regional'],
            [
                'administration_id' => null,
                'prayer_house_id' => null,
                'name' => 'Secretaria (regional)',
                'is_active' => true,
                'sort_order' => 20,
                'meta' => null,
            ],
        );

        $deptTesRegional = PublicDepartment::query()->updateOrCreate(
            ['scope' => PublicDepartment::SCOPE_REGIONAL, 'regional_id' => $regional->id, 'slug' => 'tesouraria-regional'],
            [
                'administration_id' => null,
                'prayer_house_id' => null,
                'name' => 'Tesouraria (regional)',
                'is_active' => true,
                'sort_order' => 30,
                'meta' => null,
            ],
        );

        $deptFiscalRegional = PublicDepartment::query()->updateOrCreate(
            ['scope' => PublicDepartment::SCOPE_REGIONAL, 'regional_id' => $regional->id, 'slug' => 'conselho-fiscal-regional'],
            [
                'administration_id' => null,
                'prayer_house_id' => null,
                'name' => 'Conselho fiscal (regional)',
                'is_active' => true,
                'sort_order' => 40,
                'meta' => null,
            ],
        );

        $deptSecAdmin = PublicDepartment::query()->updateOrCreate(
            ['scope' => PublicDepartment::SCOPE_ADMINISTRATION, 'administration_id' => $administration->id, 'slug' => 'secretaria-administracao'],
            [
                'regional_id' => null,
                'prayer_house_id' => null,
                'name' => 'Secretaria (administração)',
                'is_active' => true,
                'sort_order' => 10,
                'meta' => null,
            ],
        );

        $deptTesAdmin = PublicDepartment::query()->updateOrCreate(
            ['scope' => PublicDepartment::SCOPE_ADMINISTRATION, 'administration_id' => $administration->id, 'slug' => 'tesouraria-administracao'],
            [
                'regional_id' => null,
                'prayer_house_id' => null,
                'name' => 'Tesouraria (administração)',
                'is_active' => true,
                'sort_order' => 20,
                'meta' => null,
            ],
        );

        $deptTesLocalCasa = PublicDepartment::query()->updateOrCreate(
            ['scope' => PublicDepartment::SCOPE_PRAYER_HOUSE, 'prayer_house_id' => $house->id, 'slug' => 'tesouraria-escrita-local'],
            [
                'regional_id' => null,
                'administration_id' => null,
                'name' => 'Tesouraria da escrita local',
                'is_active' => true,
                'sort_order' => 10,
                'meta' => null,
            ],
        );

        $positions = [
            [$deptPresRegional, $sgEstatutarios, 'presidente', 'Presidente', 10],
            [$deptPresRegional, $sgEstatutarios, 'vice-presidente', 'Vice-presidente', 20],
            [$deptPresRegional, $sgEstatutarios, '2-vice-presidente', '2.º Vice-presidente', 30],
            [$deptSecRegional, $sgEstatutarios, 'secretario', 'Secretário', 40],
            [$deptSecRegional, $sgEstatutarios, 'vice-secretario', 'Vice-secretário', 50],
            [$deptSecRegional, $sgEstatutarios, '2-vice-secretario', '2.º Vice-secretário', 60],
            [$deptTesRegional, $sgEstatutarios, 'tesoureiro', 'Tesoureiro', 70],
            [$deptTesRegional, $sgEstatutarios, 'vice-tesoureiro', 'Vice-tesoureiro', 80],
            [$deptSecAdmin, $sgEstatutarios, 'auxiliar-administracao', 'Auxiliar da administração', 90],
            [$deptFiscalRegional, $sgEstatutarios, 'conselheiro-fiscal', 'Conselheiro fiscal', 100],
            [$deptFiscalRegional, $sgEstatutarios, 'suplente-conselheiro-fiscal', 'Suplente do conselho fiscal', 110],
            [$deptTesLocalCasa, $sgLocaisTransversal, 'tesouraria-escrita-local', 'Tesouraria da escrita local', 10],
            [$deptSecAdmin, $sgAtivo, 'responsavel-ativo-imobilizado', 'Responsável — ativo imobilizado', 10],
            [$deptSecAdmin, $sgVoluntario, 'voluntario-admin', 'Voluntário (administração)', 10],
            [$deptSecAdmin, $sgSaude, 'saude-admin', 'Saúde (administração)', 10],
        ];

        $positions = array_merge($positions, [
            [$deptPresRegional, $sgEstatutarios, 'coord-dep-presidencia-regional', 'Coordenador de departamento — Presidência (regional)', 15, true],
            [$deptSecRegional, $sgEstatutarios, 'coord-dep-secretaria-regional', 'Coordenador de departamento — Secretaria (regional)', 15, true],
            [$deptTesRegional, $sgEstatutarios, 'coord-dep-tesouraria-regional', 'Coordenador de departamento — Tesouraria (regional)', 15, true],
            [$deptSecAdmin, $sgEstatutarios, 'coord-dep-secretaria-admin', 'Coordenador de departamento — Secretaria (administração)', 15, true],
            [$deptTesAdmin, $sgEstatutarios, 'coord-dep-tesouraria-admin', 'Coordenador de departamento — Tesouraria (administração)', 15, true],
        ]);

        foreach ($positions as $row) {
            $dept = $row[0];
            $sub = $row[1];
            $slug = $row[2];
            $name = $row[3];
            $order = $row[4];
            $isCoord = (bool) ($row[5] ?? false);
            PublicPosition::query()->updateOrCreate(
                ['public_department_id' => $dept->id, 'slug' => $slug],
                [
                    'public_subgroup_id' => $sub->id,
                    'name' => $name,
                    'is_active' => true,
                    'sort_order' => $order,
                    'is_department_coordinator' => $isCoord,
                    'meta' => null,
                ],
            );
        }

        $deptMinisterioRegional = PublicDepartment::query()->updateOrCreate(
            ['scope' => PublicDepartment::SCOPE_REGIONAL, 'regional_id' => $regional->id, 'slug' => 'ministerio-regional'],
            [
                'administration_id' => null,
                'prayer_house_id' => null,
                'name' => 'Ministério (regional)',
                'is_active' => true,
                'sort_order' => 50,
                'meta' => null,
            ],
        );

        $ministerio = [
            [$deptMinisterioRegional, null, 'anciao', 'Ancião', 10],
            [$deptMinisterioRegional, null, 'diacono', 'Diácono', 20],
            [$deptMinisterioRegional, null, 'cooperador-oficio-ministerial', 'Cooperador do ofício ministerial', 30],
            [$deptMinisterioRegional, null, 'cooperador-jovens-menores', 'Cooperador de jovens e menores', 40],
        ];

        foreach ($ministerio as [$dept, $sub, $slug, $name, $order]) {
            PublicPosition::query()->updateOrCreate(
                ['public_department_id' => $dept->id, 'slug' => $slug],
                [
                    'public_subgroup_id' => $sub,
                    'name' => $name,
                    'is_active' => true,
                    'sort_order' => $order,
                    'is_department_coordinator' => false,
                    'meta' => null,
                ],
            );
        }

        $deptMusicaRegional = PublicDepartment::query()->updateOrCreate(
            ['scope' => PublicDepartment::SCOPE_REGIONAL, 'regional_id' => $regional->id, 'slug' => 'musica-regional'],
            [
                'administration_id' => null,
                'prayer_house_id' => null,
                'name' => 'Música (regional)',
                'is_active' => true,
                'sort_order' => 60,
                'meta' => null,
            ],
        );

        $musica = [
            [$deptMusicaRegional, null, 'anciao-musica', 'Ancião (música)', 10],
            [$deptMusicaRegional, null, 'encarregado-regional-musica', 'Encarregado regional de música', 20],
            [$deptMusicaRegional, null, 'encarregado-local-musica', 'Encarregado local de música', 30],
            [$deptMusicaRegional, null, 'irma-examinadora', 'Irmã examinadora', 40],
        ];

        foreach ($musica as [$dept, $sub, $slug, $name, $order]) {
            PublicPosition::query()->updateOrCreate(
                ['public_department_id' => $dept->id, 'slug' => $slug],
                [
                    'public_subgroup_id' => $sub,
                    'name' => $name,
                    'is_active' => true,
                    'sort_order' => $order,
                    'is_department_coordinator' => false,
                    'meta' => null,
                ],
            );
        }

        $subEnsaio = PublicSubgroup::query()->updateOrCreate(
            ['public_group_id' => $gMusicaEnsaio->id, 'slug' => 'coordenacao'],
            ['name' => 'Coordenação ensaio regional', 'is_active' => true, 'sort_order' => 10, 'meta' => null],
        );

        PublicPosition::query()->updateOrCreate(
            ['public_department_id' => $deptMusicaRegional->id, 'slug' => 'ensaio-regional-coordenador'],
            [
                'public_subgroup_id' => $subEnsaio->id,
                'name' => 'Coordenador de ensaio regional',
                'is_active' => true,
                'sort_order' => 50,
                'is_department_coordinator' => false,
                'meta' => null,
            ],
        );
    }
}
