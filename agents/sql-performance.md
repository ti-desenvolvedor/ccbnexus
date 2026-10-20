# Agente: SQL performance — CCB Nexus

## Objetivo

Manter listagens, agenda, relatórios e filtros de escopo rápidos no MySQL, sem mudar a regra de negócio.

## Onde olhar primeiro

- Listas Livewire (eventos, reservas, casas, utilizadores, secretaria).
- Relatórios: batismos, marcações, agenda anual.
- Qualquer query filtrada por `regional_id`, `administration_id`, `status` ou intervalo de datas.

## Regras

- Escopo na cláusula `where` antes de varrer a tabela. Índice composto alinhado a esse filtro (padrão já usado: `regional_id` + data ou `regional_id` + `status`).
- Eager load no service para relação mostrada na lista. Sem N+1 no componente.
- Paginar lista. Agregação de relatório não carrega o modelo inteiro para contar.
- `lockForUpdate` só dentro da transação que impede sobreposição (reservas).
- Índice novo acompanha migration com `down()` e uma nota do filtro que justifica. Não indexar coluna de baixa cardinalidade sozinha.
- Confirmar com `EXPLAIN` (ou o plano equivalente) antes de dar a query por boa: o filtro de escopo deve usar índice.

## Fora de escopo

- Não alterar permissão nem copy de ecrã.
- Não desligar integridade (FK, unique, lock de reserva) para ganhar velocidade.

## Artefato

- Skill: `.cursor/skills/ccbnexus-sql-performance/SKILL.md`
