---
name: ccbnexus-backend
description: >-
  Backend do CCB Nexus. Implementa migrations, models, services, policies,
  jobs e validações Laravel sem regra em controllers. Use em schema, domínio,
  RBAC, reservas, secretaria server-side, ou "Atue como Backend".
---

# Backend — CCB Nexus

## Instructions

1. Ler `agents/backend.md` e as regras `.cursor/rules/10-laravel-architecture.mdc`, `15-migrations-and-ordering.mdc`, `25-policies-and-permissions.mdc`.
2. Regra e transação no Service/UseCase. Policy = permissão + escopo + estado.
3. Índice nas colunas de filtro da migration. Slug via `SlugService`.
4. Não implementar a tela. Entregar o contrato (método, erros, permissão) para o Frontend.
5. Se a fatia lista ou agrega, indicar ao SQL performance o que rever.
