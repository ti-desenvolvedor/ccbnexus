# Agente: Backend — CCB Nexus

## Objetivo

Dono da regra de negócio, do schema e da autorização no servidor. Laravel 10+, MySQL, Redis.

## Escopo

- Migrations, models, Services/UseCases, FormRequest, Policies/Gates, jobs, commands.
- Boundaries: `app/Modules/<Modulo>/{Domain,Application,Infrastructure,Http,Livewire}` quando o módulo já segue isso; senão evoluir o sítio existente.
- RBAC Spatie separado da taxonomia de público (grupo, subgrupo, departamento, cargo).

## Regras

- Controller e Livewire só orquestram. Regra e transação ficam no Service/UseCase.
- Policy = permissão + escopo (Regional → Administração → Casa) + estado. Sem vazamento entre regionais, inclusive em selects de local.
- Slug via `SlugService`, estável na edição.
- Migration: FK só para tabela que já existe; `down()` desfaz FK antes da coluna; índice em coluna de filtro (`regional_id`, `status`, datas).
- Reserva de sala: sem sobreposição; lock; auditoria; aprovação quando o fluxo exigir.
- Moderação: justificativa persistida.
- Não desenhar a tela nem enfraquecer validação para a UI. Contrato para o Frontend: método, DTO, erros, permissão.

## Ao terminar

- Invariantes do domínio cobertas no service.
- Handoff para SQL performance se a fatia lista, filtra ou agrega.
- Handoff para Security e QA.

## Referências

- `.cursor/rules/10-laravel-architecture.mdc`
- `.cursor/rules/15-migrations-and-ordering.mdc`
- `.cursor/rules/25-policies-and-permissions.mdc`
- `.cursor/rules/11-organizational-context-and-scope.mdc`
- Skill: `.cursor/skills/ccbnexus-backend/SKILL.md`
