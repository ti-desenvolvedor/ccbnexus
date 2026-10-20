<?php

/**
 * Catálogo administrativo regional: Grupos I–V e 17 departamentos.
 * Cada departamento é semeado em scope regional e em cada administração ativa da regional.
 */
return [
    [
        'subgroup' => [
            'slug' => 'grupo-i',
            'name' => 'Grupo I — Presidência / Secretaria / Jurídico',
            'sort_order' => 10,
        ],
        'departments' => [
            ['number' => 1, 'slug' => 'presidencia', 'name' => 'Presidência', 'sort_order' => 10],
            ['number' => 2, 'slug' => 'secretaria', 'name' => 'Secretaria', 'sort_order' => 20],
            ['number' => 3, 'slug' => 'juridico', 'name' => 'Jurídico', 'sort_order' => 30],
        ],
    ],
    [
        'subgroup' => [
            'slug' => 'grupo-ii',
            'name' => 'Grupo II — Tesouraria / Contabilidade',
            'sort_order' => 20,
        ],
        'departments' => [
            ['number' => 4, 'slug' => 'tesouraria', 'name' => 'Tesouraria', 'sort_order' => 10],
            ['number' => 5, 'slug' => 'contabilidade', 'name' => 'Contabilidade', 'sort_order' => 20],
            ['number' => 6, 'slug' => 'compras', 'name' => 'Compras', 'sort_order' => 30],
            ['number' => 7, 'slug' => 'fundo-musical', 'name' => 'Fundo Musical', 'sort_order' => 40],
            ['number' => 8, 'slug' => 'distribuidora-fundo-biblico', 'name' => 'Distribuidora – Fundo Bíblico', 'sort_order' => 50],
        ],
    ],
    [
        'subgroup' => [
            'slug' => 'grupo-iii',
            'name' => 'Grupo III — Conselho Fiscal',
            'sort_order' => 30,
        ],
        'departments' => [
            ['number' => 9, 'slug' => 'conselho-fiscal', 'name' => 'Conselho Fiscal', 'sort_order' => 10],
            ['number' => 10, 'slug' => 'verificacao-auditoria', 'name' => 'Verificação (Auditoria)', 'sort_order' => 20],
        ],
    ],
    [
        'subgroup' => [
            'slug' => 'grupo-iv',
            'name' => 'Grupo IV — Patrimônio / Engenharia / Construção / Manutenção',
            'sort_order' => 40,
        ],
        'departments' => [
            ['number' => 11, 'slug' => 'engenharia', 'name' => 'Engenharia', 'sort_order' => 10],
            ['number' => 12, 'slug' => 'seguranca-manutencao-preventiva', 'name' => 'Segurança e Manutenção Preventiva', 'sort_order' => 20],
            ['number' => 13, 'slug' => 'patrimonio-bens-imoveis', 'name' => 'Patrimônio – Bens Imóveis', 'sort_order' => 30],
            ['number' => 14, 'slug' => 'patrimonio-bens-moveis', 'name' => 'Patrimônio – Bens Móveis', 'sort_order' => 40],
            ['number' => 15, 'slug' => 'voluntariado', 'name' => 'Voluntariado', 'sort_order' => 50],
        ],
    ],
    [
        'subgroup' => [
            'slug' => 'grupo-v',
            'name' => 'Grupo V — Informática / Treinamento',
            'sort_order' => 50,
        ],
        'departments' => [
            ['number' => 16, 'slug' => 'informatica', 'name' => 'Informática', 'sort_order' => 10],
            ['number' => 17, 'slug' => 'treinamento', 'name' => 'Treinamento', 'sort_order' => 20],
        ],
    ],
];
