# Desenho: RBAC escopado (D4 — aguarda decisão PO)

## Problema

Spatie `teams=false`: roles/permissions são globais. Facilitador/Moderador numa regional podem ter o mesmo papel em outra sem intenção.

## Opções

### A — Spatie teams (`teams=true`)
- Usar `team_id` = `regional_id` (ou administração).
- Prós: suporte oficial Spatie. Contras: migração de pivots; UI de atribuição mais complexa.

### B — Tabela `scoped_role_assignments`
- Colunas: `user_id`, `role_id`, `regional_id`, `administration_id?`, `prayer_house_id?`.
- Gates/Policies consultam atribuições + Spatie global para super-admin.
- Prós: evolui sem reconfigurar Spatie. Contras: código custom.

## Recomendação provisória

**Opção B** para o Nexus: menos risco com o código atual; Spatie continua a listar capabilities; o escopo fica na tabela de atribuição.

**Não implementar** até sign-off do PO.
