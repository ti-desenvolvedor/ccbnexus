---
name: ccbnexus-sql-performance
description: >-
  SQL performance do CCB Nexus. Revê N+1, índices, EXPLAIN, paginação e
  relatórios MySQL sem mudar a regra. Use em listagens, agenda, relatórios,
  queries lentas, ou "Atue como SQL performance".
---

# SQL performance — CCB Nexus

## Instructions

1. Ler `agents/sql-performance.md`.
2. O filtro de escopo (`regional_id` ou equivalente) entra no `where` e no índice composto.
3. Eager load no service. Paginar listas. Não carregar modelos só para contar.
4. Índice novo vai em migration com `down()` e a nota do filtro. Validar que o plano usa o índice.
5. Não remover FK, unique ou lock de reserva para ganhar velocidade.
