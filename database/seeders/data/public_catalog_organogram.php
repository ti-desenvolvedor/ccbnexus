<?php

/**
 * Organograma canónico do catálogo público por regional.
 * Eixos: administrative | operational | ministry | music
 */
return [
    'groups' => [
        [
            'slug' => 'colaboradores-administrativos',
            'name' => 'Colaboradores administrativos',
            'sort_order' => 10,
            'axis' => 'administrative',
            'subgroups' => [
                [
                    'slug' => 'grupo-i',
                    'name' => 'Grupo I — Presidência / Secretaria / Jurídico',
                    'sort_order' => 10,
                    'departments' => [
                        ['slug' => 'presidencia', 'name' => 'Presidência', 'number' => 1, 'sort_order' => 10, 'availability' => ['regional' => true, 'administration' => true, 'prayer_house' => false]],
                        ['slug' => 'secretaria', 'name' => 'Secretaria', 'number' => 2, 'sort_order' => 20, 'availability' => ['regional' => true, 'administration' => true, 'prayer_house' => false]],
                        ['slug' => 'juridico', 'name' => 'Jurídico', 'number' => 3, 'sort_order' => 30, 'availability' => ['regional' => true, 'administration' => true, 'prayer_house' => false]],
                    ],
                ],
                [
                    'slug' => 'grupo-ii',
                    'name' => 'Grupo II — Tesouraria / Contabilidade',
                    'sort_order' => 20,
                    'departments' => [
                        ['slug' => 'tesouraria', 'name' => 'Tesouraria', 'number' => 4, 'sort_order' => 10, 'availability' => ['regional' => true, 'administration' => true, 'prayer_house' => false]],
                        ['slug' => 'contabilidade', 'name' => 'Contabilidade', 'number' => 5, 'sort_order' => 20, 'availability' => ['regional' => true, 'administration' => true, 'prayer_house' => false]],
                        ['slug' => 'compras', 'name' => 'Compras', 'number' => 6, 'sort_order' => 30, 'availability' => ['regional' => true, 'administration' => true, 'prayer_house' => false]],
                        ['slug' => 'fundo-musical', 'name' => 'Fundo Musical', 'number' => 7, 'sort_order' => 40, 'availability' => ['regional' => true, 'administration' => true, 'prayer_house' => false]],
                        ['slug' => 'distribuidora-fundo-biblico', 'name' => 'Distribuidora – Fundo Bíblico', 'number' => 8, 'sort_order' => 50, 'availability' => ['regional' => true, 'administration' => true, 'prayer_house' => false]],
                    ],
                ],
                [
                    'slug' => 'grupo-iii',
                    'name' => 'Grupo III — Conselho Fiscal',
                    'sort_order' => 30,
                    'departments' => [
                        ['slug' => 'conselho-fiscal', 'name' => 'Conselho Fiscal', 'number' => 9, 'sort_order' => 10, 'availability' => ['regional' => true, 'administration' => true, 'prayer_house' => false]],
                        ['slug' => 'verificacao-auditoria', 'name' => 'Verificação (Auditoria)', 'number' => 10, 'sort_order' => 20, 'availability' => ['regional' => true, 'administration' => true, 'prayer_house' => false]],
                    ],
                ],
                [
                    'slug' => 'grupo-iv',
                    'name' => 'Grupo IV — Patrimônio / Engenharia / Construção / Manutenção',
                    'sort_order' => 40,
                    'departments' => [
                        ['slug' => 'engenharia', 'name' => 'Engenharia', 'number' => 11, 'sort_order' => 10, 'availability' => ['regional' => true, 'administration' => true, 'prayer_house' => false]],
                        ['slug' => 'seguranca-manutencao-preventiva', 'name' => 'Segurança e Manutenção Preventiva', 'number' => 12, 'sort_order' => 20, 'availability' => ['regional' => true, 'administration' => true, 'prayer_house' => false]],
                        ['slug' => 'patrimonio-bens-imoveis', 'name' => 'Patrimônio – Bens Imóveis', 'number' => 13, 'sort_order' => 30, 'availability' => ['regional' => true, 'administration' => true, 'prayer_house' => false]],
                        ['slug' => 'patrimonio-bens-moveis', 'name' => 'Patrimônio – Bens Móveis', 'number' => 14, 'sort_order' => 40, 'availability' => ['regional' => true, 'administration' => true, 'prayer_house' => false]],
                        ['slug' => 'voluntariado', 'name' => 'Voluntariado', 'number' => 15, 'sort_order' => 50, 'availability' => ['regional' => true, 'administration' => true, 'prayer_house' => false]],
                    ],
                ],
                [
                    'slug' => 'grupo-v',
                    'name' => 'Grupo V — Informática / Treinamento',
                    'sort_order' => 50,
                    'departments' => [
                        ['slug' => 'informatica', 'name' => 'Informática', 'number' => 16, 'sort_order' => 10, 'availability' => ['regional' => true, 'administration' => true, 'prayer_house' => false]],
                        ['slug' => 'treinamento', 'name' => 'Treinamento', 'number' => 17, 'sort_order' => 20, 'availability' => ['regional' => true, 'administration' => true, 'prayer_house' => false]],
                    ],
                ],
                [
                    'slug' => 'estatutarios',
                    'name' => 'Colaboradores estatutários',
                    'sort_order' => 60,
                    'departments' => [],
                ],
            ],
        ],
        [
            'slug' => 'servicos-operacionais',
            'name' => 'Serviços operacionais',
            'sort_order' => 15,
            'axis' => 'operational',
            'subgroups' => [
                [
                    'slug' => 'servicos-unificados',
                    'name' => 'Serviços unificados',
                    'sort_order' => 10,
                    'departments' => [
                        ['slug' => 'tesouraria-operacional', 'name' => 'Tesouraria (operacional)', 'sort_order' => 10, 'availability' => ['regional' => true, 'administration' => true, 'prayer_house' => true]],
                        ['slug' => 'ativo-imobilizado', 'name' => 'Ativo imobilizado', 'sort_order' => 20, 'availability' => ['regional' => true, 'administration' => true, 'prayer_house' => true]],
                        ['slug' => 'voluntario', 'name' => 'Voluntário', 'sort_order' => 30, 'availability' => ['regional' => true, 'administration' => true, 'prayer_house' => true]],
                        ['slug' => 'manutencao-preventiva', 'name' => 'Manutenção preventiva', 'sort_order' => 40, 'availability' => ['regional' => true, 'administration' => true, 'prayer_house' => true]],
                        ['slug' => 'cns-limpeza', 'name' => 'CNS Limpeza', 'sort_order' => 50, 'availability' => ['regional' => true, 'administration' => true, 'prayer_house' => true]],
                        ['slug' => 'saude', 'name' => 'Saúde', 'sort_order' => 60, 'availability' => ['regional' => true, 'administration' => true, 'prayer_house' => true]],
                        ['slug' => 'seguranca-operacional', 'name' => 'Segurança', 'sort_order' => 70, 'availability' => ['regional' => true, 'administration' => true, 'prayer_house' => true]],
                    ],
                ],
            ],
        ],
        [
            'slug' => 'ministerio',
            'name' => 'Ministério',
            'sort_order' => 30,
            'axis' => 'ministry',
            'subgroups' => [
                [
                    'slug' => 'ministerio',
                    'name' => 'Ministério',
                    'sort_order' => 10,
                    'departments' => [
                        ['slug' => 'ministerio', 'name' => 'Ministério', 'sort_order' => 10, 'availability' => ['regional' => true, 'administration' => true, 'prayer_house' => false]],
                    ],
                ],
            ],
        ],
        [
            'slug' => 'musica',
            'name' => 'Música',
            'sort_order' => 40,
            'axis' => 'music',
            'subgroups' => [
                [
                    'slug' => 'musica',
                    'name' => 'Música',
                    'sort_order' => 10,
                    'departments' => [
                        ['slug' => 'musica', 'name' => 'Música', 'sort_order' => 10, 'availability' => ['regional' => true, 'administration' => true, 'prayer_house' => true]],
                    ],
                ],
            ],
        ],
    ],

    /**
     * Cargos estatutários (subgrupo estatutarios) por slug de departamento administrativo.
     * Apenas regional e administração.
     */
    'statutory_positions' => [
        'presidencia' => [
            ['slug' => 'presidente', 'name' => 'Presidente', 'sort_order' => 10],
            ['slug' => 'vice-presidente', 'name' => 'Vice-presidente', 'sort_order' => 20],
            ['slug' => '2-vice-presidente', 'name' => '2.º Vice-presidente', 'sort_order' => 30],
            ['slug' => 'coord-dep-presidencia', 'name' => 'Coordenador de departamento — Presidência', 'sort_order' => 15, 'is_coordinator' => true, 'scopes' => ['regional']],
        ],
        'secretaria' => [
            ['slug' => 'secretario', 'name' => 'Secretário', 'sort_order' => 40],
            ['slug' => 'vice-secretario', 'name' => 'Vice-secretário', 'sort_order' => 50],
            ['slug' => '2-vice-secretario', 'name' => '2.º Vice-secretário', 'sort_order' => 60],
            ['slug' => 'coord-dep-secretaria', 'name' => 'Coordenador de departamento — Secretaria', 'sort_order' => 15, 'is_coordinator' => true, 'scopes' => ['regional', 'administration']],
            ['slug' => 'auxiliar-administracao', 'name' => 'Auxiliar da administração', 'sort_order' => 90, 'scopes' => ['administration']],
        ],
        'tesouraria' => [
            ['slug' => 'tesoureiro', 'name' => 'Tesoureiro', 'sort_order' => 70],
            ['slug' => 'vice-tesoureiro', 'name' => 'Vice-tesoureiro', 'sort_order' => 80],
            ['slug' => 'coord-dep-tesouraria', 'name' => 'Coordenador de departamento — Tesouraria', 'sort_order' => 15, 'is_coordinator' => true, 'scopes' => ['regional', 'administration']],
        ],
        'conselho-fiscal' => [
            ['slug' => 'conselheiro-fiscal', 'name' => 'Conselheiro fiscal', 'sort_order' => 100],
            ['slug' => 'suplente-conselheiro-fiscal', 'name' => 'Suplente do conselho fiscal', 'sort_order' => 110],
        ],
    ],

    /** Cargos por eixo e slug de departamento (quando não estatutários). */
    'department_positions' => [
        'tesouraria-operacional' => [
            ['slug' => 'responsavel', 'name' => 'Responsável', 'sort_order' => 10, 'is_coordinator' => true],
            ['slug' => 'tesoureiro-escrita-local', 'name' => 'Tesoureiro da escrita local', 'sort_order' => 20, 'scopes' => ['prayer_house']],
        ],
        'ativo-imobilizado' => [
            ['slug' => 'responsavel', 'name' => 'Responsável', 'sort_order' => 10, 'is_coordinator' => true],
        ],
        'voluntario' => [
            ['slug' => 'responsavel', 'name' => 'Responsável', 'sort_order' => 10, 'is_coordinator' => true],
        ],
        'manutencao-preventiva' => [
            ['slug' => 'responsavel', 'name' => 'Responsável', 'sort_order' => 10, 'is_coordinator' => true],
        ],
        'cns-limpeza' => [
            ['slug' => 'responsavel', 'name' => 'Responsável', 'sort_order' => 10, 'is_coordinator' => true],
        ],
        'saude' => [
            ['slug' => 'responsavel', 'name' => 'Responsável', 'sort_order' => 10, 'is_coordinator' => true],
        ],
        'seguranca-operacional' => [
            ['slug' => 'responsavel', 'name' => 'Responsável', 'sort_order' => 10, 'is_coordinator' => true],
        ],
        'ministerio' => [
            ['slug' => 'anciao', 'name' => 'Ancião', 'sort_order' => 10],
            ['slug' => 'diacono', 'name' => 'Diácono', 'sort_order' => 20],
            ['slug' => 'cooperador-oficio-ministerial', 'name' => 'Cooperador do ofício ministerial', 'sort_order' => 30],
            ['slug' => 'cooperador-jovens-menores', 'name' => 'Cooperador de jovens e menores', 'sort_order' => 40],
        ],
        'musica' => [
            ['slug' => 'anciao-musica', 'name' => 'Ancião (música)', 'sort_order' => 10, 'scopes' => ['regional']],
            ['slug' => 'anciao-secretario-musica', 'name' => 'Ancião Secretário (música)', 'sort_order' => 20, 'scopes' => ['regional']],
            ['slug' => 'anciao-darpe', 'name' => 'Ancião do DARPE', 'sort_order' => 30, 'scopes' => ['regional']],
            ['slug' => 'anciao-depac', 'name' => 'Ancião de DEPAC', 'sort_order' => 40, 'scopes' => ['regional']],
            ['slug' => 'anciao-libras', 'name' => 'Ancião de Libras', 'sort_order' => 50, 'scopes' => ['regional']],
            ['slug' => 'ensaio-regional-coordenador', 'name' => 'Coordenador de ensaio regional', 'sort_order' => 60, 'scopes' => ['regional']],
            ['slug' => 'encarregado-regional-1', 'name' => 'Encarregado regional de música (1)', 'sort_order' => 10, 'scopes' => ['administration']],
            ['slug' => 'encarregado-regional-2', 'name' => 'Encarregado regional de música (2)', 'sort_order' => 20, 'scopes' => ['administration']],
            ['slug' => 'encarregado-local', 'name' => 'Encarregado local de música', 'sort_order' => 10, 'scopes' => ['prayer_house']],
            ['slug' => 'irma-examinadora', 'name' => 'Irmã examinadora', 'sort_order' => 20, 'scopes' => ['prayer_house']],
        ],
    ],
];
