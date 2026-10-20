<?php

/**
 * Quem atende da Regional Embu das Artes (2026).
 *
 * Nomes e marcações da coluna Ancião de
 * doc-referencia/MARCACAO DE SERVICOS REGIONAL EA.pdf.
 * A planilha da agenda não traz quem atende. A lista de batismos de
 * 15/10/2026 nomeia Evandro Wagner no culto de jovens de 17/10 em
 * Barnabés; o PDF de marcações nomeia Roberto Carlos Pereira, e é
 * essa a ligação usada.
 *
 * Cada marcação liga o nome ao evento da mesma data, tipo e local.
 * Não há linha para Brás: o boletim deixa o lugar em branco e essa
 * regional só é exibida. Também ficam de fora combinações sem evento
 * na agenda (Crispim, Barnabés em setembro, Vila Iasi em 12/12,
 * Jardim Ângela, e a mocidade de novembro em Jacira e Saint Moritz,
 * cujas datas no PDF não são a do evento gravado).
 *
 * @return array{
 *     names: list<string>,
 *     markings: list<array{name: string, on: string, type: string, location: string}>
 * }
 */
return [
    'names' => [
        'Antônio Batista de Souza',
        'Aparecido Messias',
        'Benedito Monteiro',
        'Carlos Fernando Pereira',
        'Daniel Rosa',
        'Donizeti Gomes Cardoso',
        'Edson dos Reis Eugênio',
        'Eli Marcos Pereira Lima',
        'Elias Martins',
        'Elias Teixeira da Luz',
        'Eliu Nascimento Anunciação da Cruz',
        'Francisco de Assis Dias',
        'Gilmar Ribeiro de Sousa',
        'Hebert Calebe de Carvalho',
        'Israel Bastos Sarausa',
        'Izaias Soares Mariano',
        'Joel Hilário',
        'Jorge Henrique Rosa',
        'Josué Lopes Scorsi',
        'Juarez Pinheiros de Sales Filho',
        'Milton Borba de Andrade',
        'Moisés Sinani de Oliveira',
        'Nelson Carlos Antonini Zanelato',
        'Neemias Dias de Oliveira',
        'Orlando Malini de Castro',
        'Oséias Rodrigues',
        'Osvanir Bastos Viana',
        'Paulo Costa',
        'Paulo Roberto Estalise de Oliveira',
        'Roberto Carlos Pereira',
        'Roberto Vaz de Camargo',
        'Rubens Francisco do Nascimento',
        'Samuel de Oliveira',
        'Sebastião Araujo',
        'Silas Marques',
        'Valdir da Silva',
    ],
    'markings' => [
        ['name' => 'Hebert Calebe de Carvalho', 'on' => '2026-09-05', 'type' => 'culto-jovens-12', 'location' => 'Jardim Maria Rosa - Taboão da Serra'],
        ['name' => 'Aparecido Messias', 'on' => '2026-09-06', 'type' => 'batismo', 'location' => 'São Lourenço da Serra - Central'],
        ['name' => 'Josué Lopes Scorsi', 'on' => '2026-09-11', 'type' => 'culto-jovens-12', 'location' => 'Jardim Marilu – Itapecerica da Serra'],
        ['name' => 'Israel Bastos Sarausa', 'on' => '2026-09-12', 'type' => 'batismo', 'location' => 'Embu-Guaçu - Central'],
        ['name' => 'Roberto Carlos Pereira', 'on' => '2026-09-13', 'type' => 'reuniao-mocidade', 'location' => 'Cipó-Guaçu – Embu-Guaçu'],
        ['name' => 'Gilmar Ribeiro de Sousa', 'on' => '2026-09-13', 'type' => 'reuniao-mocidade', 'location' => 'Parque Paraíso – Itapecerica da Serra'],
        ['name' => 'Hebert Calebe de Carvalho', 'on' => '2026-09-19', 'type' => 'culto-jovens-12', 'location' => 'Jardim Presidente Kennedy – Embu das Artes'],
        ['name' => 'Donizeti Gomes Cardoso', 'on' => '2026-09-19', 'type' => 'culto-jovens-12', 'location' => 'Justinos – Juquitiba'],
        ['name' => 'Carlos Fernando Pereira', 'on' => '2026-09-20', 'type' => 'ensaio-regional', 'location' => 'Embu das Artes - Central'],
        ['name' => 'Daniel Rosa', 'on' => '2026-09-20', 'type' => 'ensaio-regional', 'location' => 'Juquitiba - Central'],
        ['name' => 'Milton Borba de Andrade', 'on' => '2026-09-20', 'type' => 'ensaio-regional', 'location' => 'Embu-Guaçu - Central'],
        ['name' => 'Elias Martins', 'on' => '2026-09-20', 'type' => 'ensaio-regional', 'location' => 'Jardim Saint Moritz – Taboão da Serra'],
        ['name' => 'Moisés Sinani de Oliveira', 'on' => '2026-09-26', 'type' => 'batismo', 'location' => 'Embu das Artes - Central'],
        ['name' => 'Juarez Pinheiros de Sales Filho', 'on' => '2026-10-03', 'type' => 'culto-jovens-12', 'location' => 'Jardim Maria Rosa - Taboão da Serra'],
        ['name' => 'Antônio Batista de Souza', 'on' => '2026-10-10', 'type' => 'batismo', 'location' => 'Jardim Marilu – Itapecerica da Serra'],
        ['name' => 'Carlos Fernando Pereira', 'on' => '2026-10-10', 'type' => 'batismo', 'location' => 'Juquitiba - Central'],
        ['name' => 'Moisés Sinani de Oliveira', 'on' => '2026-10-11', 'type' => 'reuniao-mocidade', 'location' => 'Jardim Marilu – Itapecerica da Serra'],
        ['name' => 'Eli Marcos Pereira Lima', 'on' => '2026-10-11', 'type' => 'reuniao-mocidade', 'location' => 'Pirajussara – Taboão da Serra'],
        ['name' => 'Paulo Costa', 'on' => '2026-10-16', 'type' => 'culto-jovens-12', 'location' => 'Embu-Guaçu - Central'],
        ['name' => 'Edson dos Reis Eugênio', 'on' => '2026-10-17', 'type' => 'culto-jovens-12', 'location' => 'Jardim Santa Tereza – Embu das Artes'],
        ['name' => 'Roberto Carlos Pereira', 'on' => '2026-10-17', 'type' => 'culto-jovens-12', 'location' => 'Barnabés – Juquitiba'],
        ['name' => 'Elias Teixeira da Luz', 'on' => '2026-10-24', 'type' => 'batismo', 'location' => 'Vila Iasi – Taboão da Serra'],
        ['name' => 'Benedito Monteiro', 'on' => '2026-10-25', 'type' => 'batismo', 'location' => 'Cipó-Guaçu – Embu-Guaçu'],
        ['name' => 'Israel Bastos Sarausa', 'on' => '2026-11-01', 'type' => 'batismo', 'location' => 'São Lourenço da Serra - Central'],
        ['name' => 'Nelson Carlos Antonini Zanelato', 'on' => '2026-11-07', 'type' => 'culto-jovens-12', 'location' => 'Jardim Maria Rosa - Taboão da Serra'],
        ['name' => 'Oséias Rodrigues', 'on' => '2026-11-14', 'type' => 'batismo', 'location' => 'Embu-Guaçu - Central'],
        ['name' => 'Izaias Soares Mariano', 'on' => '2026-11-15', 'type' => 'ensaio-regional', 'location' => 'Cipó-Guaçu – Embu-Guaçu'],
        ['name' => 'Josué Lopes Scorsi', 'on' => '2026-11-15', 'type' => 'ensaio-regional', 'location' => 'São Lourenço da Serra - Central'],
        ['name' => 'Rubens Francisco do Nascimento', 'on' => '2026-11-15', 'type' => 'ensaio-regional', 'location' => 'Vila Iasi – Taboão da Serra'],
        ['name' => 'Carlos Fernando Pereira', 'on' => '2026-11-15', 'type' => 'ensaio-regional', 'location' => 'Jardim Marilu – Itapecerica da Serra'],
        ['name' => 'Roberto Vaz de Camargo', 'on' => '2026-11-15', 'type' => 'ensaio-regional', 'location' => 'Jardim Vista Alegre – Embu das Artes'],
        ['name' => 'Joel Hilário', 'on' => '2026-11-21', 'type' => 'culto-jovens-12', 'location' => 'Jardim Presidente Kennedy – Embu das Artes'],
        ['name' => 'Jorge Henrique Rosa', 'on' => '2026-11-21', 'type' => 'culto-jovens-12', 'location' => 'Justinos – Juquitiba'],
        ['name' => 'Silas Marques', 'on' => '2026-11-28', 'type' => 'batismo', 'location' => 'Embu das Artes - Central'],
        ['name' => 'Roberto Carlos Pereira', 'on' => '2026-12-05', 'type' => 'culto-jovens-12', 'location' => 'Jardim Maria Rosa - Taboão da Serra'],
        ['name' => 'Sebastião Araujo', 'on' => '2026-12-12', 'type' => 'batismo', 'location' => 'Jardim Marilu – Itapecerica da Serra'],
        ['name' => 'Hebert Calebe de Carvalho', 'on' => '2026-12-13', 'type' => 'reuniao-mocidade', 'location' => 'Embu-Guaçu - Central'],
        ['name' => 'Francisco de Assis Dias', 'on' => '2026-12-13', 'type' => 'reuniao-mocidade', 'location' => 'Jardim Santa Tereza – Embu das Artes'],
        ['name' => 'Neemias Dias de Oliveira', 'on' => '2026-12-13', 'type' => 'reuniao-mocidade', 'location' => 'Juquitiba - Central'],
        ['name' => 'Carlos Fernando Pereira', 'on' => '2026-12-13', 'type' => 'reuniao-mocidade', 'location' => 'Vila Santa Maria – Itapecerica da Serra'],
        ['name' => 'Neemias Dias de Oliveira', 'on' => '2026-12-13', 'type' => 'batismo', 'location' => 'Juquitiba - Central'],
        ['name' => 'Paulo Roberto Estalise de Oliveira', 'on' => '2026-12-18', 'type' => 'culto-jovens-12', 'location' => 'Embu-Guaçu - Central'],
        ['name' => 'Valdir da Silva', 'on' => '2026-12-19', 'type' => 'culto-jovens-12', 'location' => 'Jardim Santa Tereza – Embu das Artes'],
        ['name' => 'Samuel de Oliveira', 'on' => '2026-12-19', 'type' => 'culto-jovens-12', 'location' => 'Barnabés – Juquitiba'],
    ],
];
