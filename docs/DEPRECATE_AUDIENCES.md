# Decisão: deprecar `audiences` em favor de `public_*`

**Data:** 2026-10-15  
**Estado:** aceite — migração gradual

## Decisão

O catálogo plano `audiences` / pivot `audience_event` fica **legado**. O caminho canónico é a taxonomia:

`public_groups` → `public_subgroups` → `public_departments` → `public_positions`  
com pivot `event_public_position`.

## Compatibilidade

1. UI de eventos já preferem `public_position_ids`.
2. Menu marca “Audiências (legado)”.
3. Seeds de `audiences` podem permanecer até remoção; novos eventos não devem depender delas.
4. Relatórios futuros filtram por cargos/`public_*` apenas.

## Próximos passos

- [ ] Parar de seedar novos audiences planos
- [ ] Script opcional: mapear audience legado → public_group/position 1:1
- [ ] Remover rotas/CRUD legado após um ciclo de release
