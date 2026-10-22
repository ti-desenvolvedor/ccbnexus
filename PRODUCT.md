# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

O utilizador principal é o **facilitador da regional**. Trabalha no painel autenticado, com um contexto ativo (Regional → Administração → Casa de oração), e trata filas de acesso, aprovações, reservas de sala e eventos.

Hoje o produto serve **cadastrados** (quem tem acesso ao painel). Confirmar presença em eventos e visualizar agendas, para pessoas além desse círculo de operação, é expansão futura — não é o trabalho que o desenho deve assumir como já entregue.

## Product Purpose

O CCB Nexus é o painel com que uma regional da Congregação Cristã no Brasil opera agenda, eventos, reservas e aprovações, com permissão explícita e escopo organizacional.

Sucesso, para o facilitador, é concluir esse trabalho dentro do contexto ativo, sem ver nem alterar dados de outra regional.

## Positioning

Um calendário genérico de igreja não pode afirmar, com verdade, o mecanismo deste produto: autorização por **permissão + escopo + estado** na hierarquia Regional → Administração → Casa de oração, com papéis de sistema (quem opera o painel) separados dos cargos do catálogo de domínio (Ancião, Tesoureiro, coordenador de departamento, e afins).

## Operating Context

O facilitador usa o painel web em português do Brasil, em secretária e no telemóvel. Quando a conta alcança mais de um escopo, escolhe o contexto ativo antes de ações sensíveis; esse contexto permanece visível.

O trabalho diário são filas: pedidos de acesso, aprovações, reservas de sala e eventos. Ações sensíveis de moderação pedem justificativa e ficam em auditoria.

Peças oficiais, como a lista de batismos em PDF, saem em nome da Congregação Cristã no Brasil e da regional do relatório.

## Capabilities and Constraints

Confirmado nesta entrevista e no produto em uso:

- Painel web autenticado (Laravel, Livewire, Tailwind, MySQL, Redis). O acesso entra por solicitação e aprovação; o auto-registo não é o caminho de produção.
- Escopo Regional → Administração → Casa de oração. Listagens, locais, salas, eventos e catálogo não atravessam regionais.
- Interface e documentos em português do Brasil.
- Filas operacionais usáveis no telemóvel, com alvos de toque de pelo menos 44 px.
- Nome da aplicação: CCB Nexus (`APP_NAME`, omissão `CCB NEXUS`).

Em aberto, não fechado nesta entrevista:

- Confirmação de presença e visualização de agendas para pessoas que não operam o painel. Direção de produto declarada como futura.
- Modelo de RBAC por regional (Spatie teams ou papéis com escopo). O desenho está em `docs/RBAC_SCOPED_DESIGN.md`; a escolha não foi feita aqui.
- Cadastro de pessoas e filiação N:N a cargos. Fatia posterior no escopo; o desenho não deve assumir um único cargo por pessoa.

## Brand Commitments

- Nome: **CCB Nexus**. Marca no painel: `resources/views/components/brand-logo.blade.php`.
- Documentos oficiais identificam **Congregação Cristã no Brasil** (cabeçalho da lista de batismos em `resources/views/reports/baptism-list/pdf.blade.php`).

Não há voz, personalidade ou referência visual adicional tornada obrigatória nesta entrevista.

## Evidence on Hand

- Escopo de produto: `docs/CCB_NEXUS_SCOPE.md`.
- Contrato de usabilidade do painel: `agents/usabilidade.md`.
- Marca e cabeçalho institucional: caminhos na secção anterior.

Não há depoimentos, estudos de caso, imprensa, preços nem licenciamento de produto no repositório. O `README.md` é o texto padrão do Laravel. Trabalho futuro não deve inventar prova social, clientes ou números.

## Product Principles

1. O facilitador termina a tarefa no contexto ativo, sem procurar onde está o escopo.
2. Permissão, escopo e estado mandam em cada ação sensível. Outra regional não aparece.
3. Papel de sistema e cargo do catálogo são coisas diferentes.
4. Português do Brasil e o nome institucional nos documentos oficiais fazem parte do produto.
5. A fila que o facilitador usa no telemóvel continua utilizável, com alvos de pelo menos 44 px.
